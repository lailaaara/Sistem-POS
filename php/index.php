<?php
declare(strict_types=1);

namespace Resto;

require_once __DIR__ . '/model/Pengguna.php';
require_once __DIR__ . '/model/Kasir.php';
require_once __DIR__ . '/model/Manajer.php';
require_once __DIR__ . '/model/Menu.php';
require_once __DIR__ . '/model/Makanan.php';
require_once __DIR__ . '/model/Minuman.php';
require_once __DIR__ . '/model/ItemPesanan.php';
require_once __DIR__ . '/model/Pesanan.php';
require_once __DIR__ . '/model/Diskon.php';
require_once __DIR__ . '/model/DiskonMember.php';
require_once __DIR__ . '/model/DiskonVoucher.php';
require_once __DIR__ . '/model/Pembayaran.php';
require_once __DIR__ . '/model/Tunai.php';
require_once __DIR__ . '/model/QRIS.php';
require_once __DIR__ . '/model/Struk.php';
require_once __DIR__ . '/service/Storage.php';

use Resto\Model\Kasir;
use Resto\Model\Manajer;
use Resto\Model\Makanan;
use Resto\Model\Minuman;
use Resto\Model\ItemPesanan;
use Resto\Model\Pesanan;
use Resto\Model\DiskonMember;
use Resto\Model\DiskonVoucher;
use Resto\Model\Tunai;
use Resto\Model\QRIS;
use Resto\Service\Storage;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$storage = new Storage();
$katalog = $storage->getKatalogMenu();

// Inisialisasi State Sesi
if (!isset($_SESSION['peran'])) {
    $_SESSION['peran'] = null; // 'kasir' | 'manajer'
}
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = []; // Array of serializable item data
}
if (!isset($_SESSION['nomorMeja'])) {
    $_SESSION['nomorMeja'] = 1;
}

$pesanError = '';
$pesanSukses = '';
$strukSelesai = null;

// --- Handle Request POST ---
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login_kasir') {
        $kasir = new Kasir("KSR-01", "Budi", "12345");
        $kasir->login();
        $_SESSION['peran'] = 'kasir';
        $_SESSION['nama'] = $kasir->getNama();
        $_SESSION['idUser'] = $kasir->getIdPengguna();
    } elseif ($action === 'login_manajer') {
        $manajer = new Manajer("MNJ-01", "Siska", "admin");
        $manajer->login();
        $_SESSION['peran'] = 'manajer';
        $_SESSION['nama'] = $manajer->getNama();
        $_SESSION['idUser'] = $manajer->getIdPengguna();
    } elseif ($action === 'logout') {
        session_destroy();
        header("Location: index.php");
        exit;
    } elseif ($action === 'tambah_keranjang' && $_SESSION['peran'] === 'kasir') {
        $idMenu = $_POST['idMenu'] ?? '';
        $jumlah = (int) ($_POST['jumlah'] ?? 1);
        $catatan = trim((string) ($_POST['catatan'] ?? ''));
        $dipanaskan = isset($_POST['dipanaskan']);
        $ukuran = $_POST['ukuran'] ?? 'Regular';
        $suhu = $_POST['suhu'] ?? 'Dingin';

        $menuDipilih = null;
        foreach ($katalog as $m) {
            if ($m->getIdMenu() === $idMenu) {
                $menuDipilih = $m;
                break;
            }
        }

        if ($menuDipilih && $jumlah > 0) {
            // Hitung akumulasi
            $totalTerpesan = $jumlah;
            foreach ($_SESSION['keranjang'] as $item) {
                if ($item['idMenu'] === $idMenu) {
                    $totalTerpesan += $item['jumlah'];
                }
            }

            if ($totalTerpesan <= $menuDipilih->getStok()) {
                $_SESSION['keranjang'][] = [
                    'idMenu' => $menuDipilih->getIdMenu(),
                    'nama' => $menuDipilih->getNama(),
                    'hargaDasar' => $menuDipilih->getHarga(),
                    'tipe' => ($menuDipilih instanceof Makanan) ? 'Makanan' : 'Minuman',
                    'dipanaskan' => $dipanaskan,
                    'ukuran' => $ukuran,
                    'suhu' => $suhu,
                    'jumlah' => $jumlah,
                    'catatan' => $catatan
                ];
                $pesanSukses = "Berhasil menambahkan " . htmlspecialchars($menuDipilih->getNama()) . " ke keranjang.";
            } else {
                $pesanError = "Stok tidak mencukupi untuk " . htmlspecialchars($menuDipilih->getNama()) . " (Sisa stok: " . $menuDipilih->getStok() . ").";
            }
        }
    } elseif ($action === 'hapus_keranjang') {
        $index = (int) ($_POST['index'] ?? -1);
        if (isset($_SESSION['keranjang'][$index])) {
            array_splice($_SESSION['keranjang'], $index, 1);
        }
    } elseif ($action === 'kosongkan_keranjang') {
        $_SESSION['keranjang'] = [];
    } elseif ($action === 'proses_bayar') {
        $nomorMeja = max(1, (int) ($_POST['nomorMeja'] ?? 1));
        $_SESSION['nomorMeja'] = $nomorMeja;

        if (empty($_SESSION['keranjang'])) {
            $pesanError = "Keranjang masih kosong. Tambahkan minimal 1 menu.";
        } else {
            // Bangun objek Pesanan sesuai OOP UML
            $kasir = new Kasir($_SESSION['idUser'] ?? 'KSR-01', $_SESSION['nama'] ?? 'Kasir', '12345');
            $idPesanan = 'ORD-' . strtoupper(substr(uniqid(), -4));
            $pesanan = $kasir->buatPesanan($idPesanan, $nomorMeja);

            // Tambahkan item
            foreach ($_SESSION['keranjang'] as $cartItem) {
                if ($cartItem['tipe'] === 'Makanan') {
                    $menuObj = new Makanan($cartItem['idMenu'], $cartItem['nama'], (float) $cartItem['hargaDasar'], 999, (bool) $cartItem['dipanaskan']);
                } else {
                    $menuObj = new Minuman($cartItem['idMenu'], $cartItem['nama'], (float) $cartItem['hargaDasar'], 999, (string) $cartItem['ukuran'], (string) $cartItem['suhu']);
                }
                $itemPesanan = new ItemPesanan($menuObj, (int) $cartItem['jumlah'], (string) $cartItem['catatan']);
                $pesanan->tambahItem($itemPesanan);
            }

            // Terapkan Diskon
            $tipeDiskon = $_POST['tipeDiskon'] ?? 'none';
            if ($tipeDiskon === 'member') {
                $persen = (float) ($_POST['persenDiskon'] ?? 0);
                $pesanan->setDiskon(new DiskonMember($persen));
            } elseif ($tipeDiskon === 'voucher') {
                $nominal = (float) ($_POST['nominalVoucher'] ?? 0);
                $pesanan->setDiskon(new DiskonVoucher($nominal));
            }

            $totalTagihan = $pesanan->hitungTotal();
            $metodeBayar = $_POST['metodeBayar'] ?? 'tunai';
            $suksesBayar = false;
            $objPembayaran = null;

            if ($metodeBayar === 'tunai') {
                $uangDiterima = (float) ($_POST['uangDiterima'] ?? 0);
                $tunai = new Tunai($uangDiterima);
                if ($tunai->prosesPembayaran($totalTagihan)) {
                    $objPembayaran = $tunai;
                    $suksesBayar = true;
                } else {
                    $pesanError = "Pembayaran tunai gagal: Uang diterima (Rp " . number_format($uangDiterima, 0, ',', '.') . ") kurang dari total tagihan (Rp " . number_format($totalTagihan, 0, ',', '.') . ").";
                }
            } else {
                $kodeQR = trim((string) ($_POST['kodeQR'] ?? ''));
                $qris = new QRIS($kodeQR);
                if ($qris->prosesPembayaran($totalTagihan)) {
                    $objPembayaran = $qris;
                    $suksesBayar = true;
                } else {
                    $pesanError = "Pembayaran QRIS gagal: Kode QR harus diawali 'QRIS-'.";
                }
            }

            if ($suksesBayar && $objPembayaran !== null) {
                $pesanan->setPembayaran($objPembayaran);
                $struk = $pesanan->buatStruk();
                $pesanan->setStatus(Pesanan::SELESAI);

                // Kurangi stok katalog lokal
                foreach ($pesanan->getItems() as $item) {
                    foreach ($katalog as $m) {
                        if ($m->getIdMenu() === $item->getMenu()->getIdMenu()) {
                            $m->kurangiStok($item->getJumlah());
                        }
                    }
                }
                $storage->simpanKatalogMenu($katalog);
                $storage->simpanPesanan($pesanan);

                // Tampilkan struk
                $strukSelesai = [
                    'idPesanan' => $pesanan->getIdPesanan(),
                    'noStruk' => $struk->getNoStruk(),
                    'meja' => $pesanan->getNomorMeja(),
                    'tanggal' => $pesanan->getTanggal()->format('d/m/Y H:i:s'),
                    'items' => $pesanan->getItems(),
                    'subtotal' => $pesanan->hitungSubtotal(),
                    'diskon' => $pesanan->hitungSubtotal() - $pesanan->hitungTotal(),
                    'total' => $pesanan->hitungTotal(),
                    'pembayaran' => $objPembayaran
                ];

                $_SESSION['keranjang'] = [];
                $pesanSukses = "Transaksi berhasil diproses!";
            }
        }
    }
}

// Rekonstruksi keranjang untuk subtotal sementara
$subtotalKeranjang = 0.0;
foreach ($_SESSION['keranjang'] as $c) {
    $harga = $c['hargaDasar'];
    if ($c['tipe'] === 'Makanan' && !empty($c['dipanaskan'])) {
        $harga += 2000.0;
    } elseif ($c['tipe'] === 'Minuman' && strcasecmp($c['ukuran'], 'Large') === 0) {
        $harga += 3000.0;
    }
    $subtotalKeranjang += $harga * $c['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem POS Restoran</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #ca8a04;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text);
            padding-bottom: 50px;
        }

        .navbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .grid-layout {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 1.5rem;
        }

        @media (max-width: 900px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .card h2 {
            font-size: 1.15rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: var(--success);
            border: 1px solid #bbf7d0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-secondary {
            background: var(--muted);
            color: #fff;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-sm {
            padding: 0.25rem 0.6rem;
            font-size: 0.8rem;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem;
        }

        .menu-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .menu-item h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .menu-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .menu-stock {
            font-size: 0.8rem;
            color: var(--muted);
            margin-bottom: 0.75rem;
        }

        .form-group {
            margin-bottom: 0.85rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        th,
        td {
            padding: 0.65rem 0.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            font-weight: 600;
            color: var(--muted);
            background: #f8fafc;
        }

        .struk-box {
            background: #fafafa;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 1.5rem;
            font-family: monospace;
            font-size: 0.95rem;
        }

        .struk-box hr {
            border: none;
            border-top: 1px dashed #cbd5e1;
            margin: 0.75rem 0;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-food {
            background: #fed7aa;
            color: #9a3412;
        }

        .badge-drink {
            background: #bae6fd;
            color: #0369a1;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <h1>SISTEM POS RESTORAN</h1>
        <div>
            <?php if (!empty($_SESSION['peran'])): ?>
                <span style="margin-right: 1rem; font-size: 0.9rem;">
                    Login: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> (<?= ucfirst($_SESSION['peran']) ?>)
                </span>
                <form action="index.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-outline btn-sm">Logout</button>
                </form>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($pesanError)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($pesanError) ?></div>
        <?php endif; ?>
        <?php if (!empty($pesanSukses)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($pesanSukses) ?></div>
        <?php endif; ?>

        <?php if (empty($_SESSION['peran'])): ?>
            <!-- Halaman Pilih Peran / Login -->
            <div class="card" style="max-width: 500px; margin: 3rem auto; text-align: center;">
                <h2>Masuk ke Sistem POS</h2>
                <p style="color: var(--muted); margin-bottom: 1.5rem; font-size: 0.9rem;">Pilih peran pengguna untuk memulai
                    transaksi:</p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="login_kasir">
                        <button type="submit" class="btn btn-primary" style="padding: 1rem 1.5rem;">
                            Masuk sebagai Kasir (Budi)
                        </button>
                    </form>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="login_manajer">
                        <button type="submit" class="btn btn-secondary" style="padding: 1rem 1.5rem;">
                            Masuk sebagai Manajer (Siska)
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($_SESSION['peran'] === 'manajer'): ?>
            <!-- Tampilan Khusus Manajer (Laporan) -->
            <?php
            $manajer = new Manajer($_SESSION['idUser'], $_SESSION['nama'], "admin");
            $riwayat = $storage->getRiwayatPesanan();
            $totalOmzet = array_sum(array_column($riwayat, 'total'));
            ?>
            <div class="card">
                <h2>
                    <span>Rekap Laporan Penjualan (Manajer: <?= htmlspecialchars($manajer->getNama()) ?>)</span>
                    <span class="badge" style="background:#e0e7ff; color:#3730a3;">Total Omzet: Rp
                        <?= number_format($totalOmzet, 0, ',', '.') ?></span>
                </h2>
                <p style="color: var(--muted); font-size: 0.85rem; margin-bottom: 1rem;">
                    Data ini dibaca secara lokal langsung dari file JSON penyimpanan (<code>php/data/pesanan.json</code>).
                </p>

                <?php if (empty($riwayat)): ?>
                    <p style="color: var(--muted); text-align: center; padding: 2rem;">Belum ada riwayat transaksi yang
                        tersimpan.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Pesanan</th>
                                <th>Tanggal</th>
                                <th>Meja</th>
                                <th>Item Dipesan</th>
                                <th>Metode Bayar</th>
                                <th>Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $idx => $r): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($r['idPesanan']) ?></strong><br><small
                                            style="color:var(--muted);"><?= htmlspecialchars($r['noStruk'] ?? '-') ?></small></td>
                                    <td><?= htmlspecialchars($r['tanggal']) ?></td>
                                    <td>Meja <?= $r['nomorMeja'] ?></td>
                                    <td>
                                        <ul style="padding-left: 1rem; font-size: 0.85rem;">
                                            <?php foreach ($r['items'] as $it): ?>
                                                <li><?= $it['jumlah'] ?>x <?= htmlspecialchars($it['nama']) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td><span class="badge"
                                            style="background:#e2e8f0;"><?= htmlspecialchars($r['pembayaranTipe']) ?></span></td>
                                    <td><strong>Rp <?= number_format($r['total'], 0, ',', '.') ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Tampilan Kasir (Pemesanan & Kasir Interaktif) -->

            <?php if ($strukSelesai !== null): ?>
                <!-- Popup Modal Cetak Struk Hasil Transaksi -->
                <div class="card" style="border: 2px solid var(--success); background: #f0fdf4;">
                    <h2>
                        <span>Struk Pembayaran Resmi</span>
                        <a href="index.php" class="btn btn-outline btn-sm">Tutup / Transaksi Baru</a>
                    </h2>
                    <div class="struk-box">
                        <div style="text-align: center;">
                            <strong>RESTORAN NUSANTARA</strong><br>
                            Sistem POS Restoran<br>
                            ====================================<br>
                            No. Struk: <?= htmlspecialchars($strukSelesai['noStruk']) ?><br>
                            ID Pesanan: <?= htmlspecialchars($strukSelesai['idPesanan']) ?><br>
                            Waktu: <?= htmlspecialchars($strukSelesai['tanggal']) ?><br>
                            No. Meja: <?= $strukSelesai['meja'] ?><br>
                        </div>
                        <hr>
                        <?php foreach ($strukSelesai['items'] as $item): ?>
                            <?php
                            $nama = $item->getMenu()->getNama();
                            if ($item->getMenu() instanceof Makanan && $item->getMenu()->isDipanaskan()) {
                                $nama .= " (Dipanaskan)";
                            } elseif ($item->getMenu() instanceof Minuman) {
                                $nama .= " (" . $item->getMenu()->getUkuran() . ", " . $item->getMenu()->getSuhu() . ")";
                            }
                            ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span><?= $item->getJumlah() ?>x <?= htmlspecialchars($nama) ?></span>
                                <span>Rp <?= number_format($item->hitungSubtotal(), 0, ',', '.') ?></span>
                            </div>
                            <?php if ($item->getCatatan() !== ''): ?>
                                <div style="font-size: 0.8rem; color: #64748b; padding-left: 1rem;">*
                                    <?= htmlspecialchars($item->getCatatan()) ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <hr>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Subtotal</span>
                                <span>Rp <?= number_format($strukSelesai['subtotal'], 0, ',', '.') ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Diskon</span>
                                <span>-Rp <?= number_format($strukSelesai['diskon'], 0, ',', '.') ?></span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem; margin-top: 0.5rem;">
                                <span>TOTAL BAYAR</span>
                                <span>Rp <?= number_format($strukSelesai['total'], 0, ',', '.') ?></span>
                            </div>
                            <hr>
                            <?php if ($strukSelesai['pembayaran'] instanceof Tunai): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Tunai Diterima</span>
                                    <span>Rp
                                        <?= number_format($strukSelesai['pembayaran']->getUangDiterima(), 0, ',', '.') ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Kembalian</span>
                                    <span>Rp
                                        <?= number_format($strukSelesai['pembayaran']->hitungKembalian(), 0, ',', '.') ?></span>
                                </div>
                            <?php elseif ($strukSelesai['pembayaran'] instanceof QRIS): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Metode</span>
                                    <span>QRIS (<?= htmlspecialchars($strukSelesai['pembayaran']->getKodeQR()) ?>)</span>
                                </div>
                            <?php endif; ?>
                            <div style="text-align: center; margin-top: 1rem; color: #64748b;">
                                Terima Kasih Atas Kunjungan Anda!
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid-layout">
                    <!-- Kolom Kiri: Katalog Menu Restoran -->
                    <div>
                        <div class="card">
                            <h2>Katalog Menu Restoran</h2>
                            <div class="menu-grid">
                                <?php foreach ($katalog as $m): ?>
                                    <div class="menu-item">
                                        <div>
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: flex-start;">
                                                <h3><?= htmlspecialchars($m->getNama()) ?></h3>
                                                <span
                                                    class="badge <?= ($m instanceof Makanan) ? 'badge-food' : 'badge-drink' ?>">
                                                    <?= ($m instanceof Makanan) ? 'Makanan' : 'Minuman' ?>
                                                </span>
                                            </div>
                                            <div class="menu-price">Rp <?= number_format($m->getHarga(), 0, ',', '.') ?></div>
                                            <div class="menu-stock">Stok Tersedia: <strong><?= $m->getStok() ?></strong></div>
                                        </div>

                                        <form action="index.php" method="POST">
                                            <input type="hidden" name="action" value="tambah_keranjang">
                                            <input type="hidden" name="idMenu" value="<?= htmlspecialchars($m->getIdMenu()) ?>">

                                            <?php if ($m instanceof Makanan): ?>
                                                <div class="form-group" style="margin-bottom: 0.5rem;">
                                                    <label style="font-weight: normal; cursor: pointer;">
                                                        <input type="checkbox" name="dipanaskan" value="1"> Dipanaskan (+Rp 2.000)
                                                    </label>
                                                </div>
                                            <?php elseif ($m instanceof Minuman): ?>
                                                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                    <select name="ukuran" class="form-control"
                                                        style="font-size: 0.8rem; padding: 0.3rem;">
                                                        <option value="Regular">Regular</option>
                                                        <option value="Large">Large (+3rb)</option>
                                                    </select>
                                                    <select name="suhu" class="form-control"
                                                        style="font-size: 0.8rem; padding: 0.3rem;">
                                                        <option value="Dingin">Dingin</option>
                                                        <option value="Panas">Panas</option>
                                                    </select>
                                                </div>
                                            <?php endif; ?>

                                            <div style="display: flex; gap: 0.5rem;">
                                                <input type="number" name="jumlah" value="1" min="1" max="<?= $m->getStok() ?>"
                                                    class="form-control" style="width: 70px; text-align: center;">
                                                <input type="text" name="catatan" placeholder="Catatan..." class="form-control"
                                                    style="flex:1;">
                                                <button type="submit" class="btn btn-primary btn-sm" <?= ($m->getStok() <= 0) ? 'disabled style="background:var(--muted);"' : '' ?>>
                                                    + Tambah
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Keranjang, Diskon & Pembayaran -->
                    <div>
                        <div class="card">
                            <h2>
                                <span>Keranjang Pesanan</span>
                                <?php if (!empty($_SESSION['keranjang'])): ?>
                                    <form action="index.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="kosongkan_keranjang">
                                        <button type="submit" class="btn btn-outline btn-sm"
                                            style="color:var(--danger); border-color:var(--danger);">Kosongkan</button>
                                    </form>
                                <?php endif; ?>
                            </h2>

                            <?php if (empty($_SESSION['keranjang'])): ?>
                                <p style="color: var(--muted); text-align: center; padding: 2rem 0;">Keranjang masih kosong.</p>
                            <?php else: ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Jumlah</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($_SESSION['keranjang'] as $idx => $c): ?>
                                            <?php
                                            $hargaItem = $c['hargaDasar'];
                                            $opsi = [];
                                            if ($c['tipe'] === 'Makanan' && !empty($c['dipanaskan'])) {
                                                $hargaItem += 2000.0;
                                                $opsi[] = "Dipanaskan";
                                            } elseif ($c['tipe'] === 'Minuman') {
                                                if (strcasecmp($c['ukuran'], 'Large') === 0)
                                                    $hargaItem += 3000.0;
                                                $opsi[] = $c['ukuran'] . ', ' . $c['suhu'];
                                            }
                                            $sub = $hargaItem * $c['jumlah'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($c['nama']) ?></strong>
                                                    <?php if (!empty($opsi)): ?>
                                                        <span
                                                            style="font-size: 0.75rem; color: var(--muted);"><br>(<?= implode(', ', $opsi) ?>)</span>
                                                    <?php endif; ?>
                                                    <?php if ($c['catatan'] !== ''): ?>
                                                        <span style="font-size: 0.75rem; color: var(--primary);"><br>*
                                                            <?= htmlspecialchars($c['catatan']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $c['jumlah'] ?>x</td>
                                                <td>Rp <?= number_format($sub, 0, ',', '.') ?></td>
                                                <td style="text-align: right;">
                                                    <form action="index.php" method="POST">
                                                        <input type="hidden" name="action" value="hapus_keranjang">
                                                        <input type="hidden" name="index" value="<?= $idx ?>">
                                                        <button type="submit" class="btn btn-outline btn-sm"
                                                            style="color:var(--danger); border-color:transparent;">✕</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div
                                    style="display: flex; justify-content: space-between; margin-top: 1rem; font-weight: bold; font-size: 1.05rem;">
                                    <span>Subtotal Kotor:</span>
                                    <span>Rp <?= number_format($subtotalKeranjang, 0, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($_SESSION['keranjang'])): ?>
                            <!-- Form Diskon & Pembayaran -->
                            <div class="card">
                                <h2>Checkout & Pembayaran</h2>
                                <form action="index.php" method="POST">
                                    <input type="hidden" name="action" value="proses_bayar">

                                    <div class="form-group">
                                        <label>Nomor Meja Pelanggan:</label>
                                        <input type="number" name="nomorMeja" value="<?= $_SESSION['nomorMeja'] ?>" min="1"
                                            max="50" class="form-control" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Pilihan Diskon:</label>
                                        <select name="tipeDiskon" id="tipeDiskon" class="form-control"
                                            onchange="toggleDiskonInput()">
                                            <option value="none">Tanpa Diskon</option>
                                            <option value="member">Diskon Member (%)</option>
                                            <option value="voucher">Diskon Voucher (Rp)</option>
                                        </select>
                                    </div>

                                    <div class="form-group" id="groupMember" style="display:none;">
                                        <label>Persentase Diskon Member (0-100%):</label>
                                        <input type="number" name="persenDiskon" value="10" min="0" max="100"
                                            class="form-control">
                                    </div>

                                    <div class="form-group" id="groupVoucher" style="display:none;">
                                        <label>Nominal Voucher Potongan (Rp):</label>
                                        <input type="number" name="nominalVoucher" value="10000" min="0" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label>Metode Pembayaran:</label>
                                        <select name="metodeBayar" id="metodeBayar" class="form-control"
                                            onchange="toggleBayarInput()">
                                            <option value="tunai">Tunai (Cash)</option>
                                            <option value="qris">QRIS Digital</option>
                                        </select>
                                    </div>

                                    <div class="form-group" id="groupTunai">
                                        <label>Uang Tunai Diterima (Rp):</label>
                                        <input type="number" name="uangDiterima" value="<?= $subtotalKeranjang ?>" min="0"
                                            class="form-control">
                                    </div>

                                    <div class="form-group" id="groupQRIS" style="display:none;">
                                        <label>Kode String QRIS (Harus diawali 'QRIS-'):</label>
                                        <input type="text" name="kodeQR" value="QRIS-001" class="form-control">
                                    </div>

                                    <button type="submit" class="btn btn-primary"
                                        style="width: 100%; padding: 0.75rem; margin-top: 0.5rem; font-size: 1rem;">
                                        Proses Pembayaran & Cetak Struk
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
            function toggleDiskonInput() {
                var val = document.getElementById('tipeDiskon').value;
                document.getElementById('groupMember').style.display = (val === 'member') ? 'block' : 'none';
                document.getElementById('groupVoucher').style.display = (val === 'voucher') ? 'block' : 'none';
            }
            function toggleBayarInput() {
                var val = document.getElementById('metodeBayar').value;
                document.getElementById('groupTunai').style.display = (val === 'tunai') ? 'block' : 'none';
                document.getElementById('groupQRIS').style.display = (val === 'qris') ? 'block' : 'none';
            }
        </script>
</body>

</html>