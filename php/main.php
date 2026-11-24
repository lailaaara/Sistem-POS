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
use Resto\Model\Menu;
use Resto\Model\Makanan;
use Resto\Model\Minuman;
use Resto\Model\ItemPesanan;
use Resto\Model\Pesanan;
use Resto\Model\DiskonMember;
use Resto\Model\DiskonVoucher;
use Resto\Model\Tunai;
use Resto\Model\QRIS;
use Resto\Service\Storage;

class MainCLI {
    private Storage $storage;
    /** @var Menu[] */
    private array $katalogMenu;
    private int $urutanPesanan = 1;

    public function __construct() {
        $this->storage = new Storage();
        $this->katalogMenu = $this->storage->getKatalogMenu();
    }

    public function run(): void {
        $berjalan = true;
        while ($berjalan) {
            echo "\n====================================================\n";
            echo "       SISTEM PEMESANAN & PEMBAYARAN RESTORAN (PHP) \n";
            echo "====================================================\n";
            echo "1. Login sebagai Kasir\n";
            echo "2. Login sebagai Manajer\n";
            echo "0. Keluar Aplikasi\n";
            echo "----------------------------------------------------\n";

            $pilihan = $this->bacaInt("Pilih peran [0-2]: ", 0, 2);
            switch ($pilihan) {
                case 1:
                    $this->jalankanSesiKasir();
                    break;
                case 2:
                    $this->jalankanSesiManajer();
                    break;
                case 0:
                    $berjalan = false;
                    echo "Terima kasih. Program ditutup.\n";
                    break;
            }
        }
    }

    private function jalankanSesiKasir(): void {
        echo "Masukkan Nama Kasir (Enter untuk 'Budi'): ";
        $nama = trim((string)fgets(STDIN));
        if ($nama === '') {
            $nama = "Budi";
        }

        $kasir = new Kasir("KSR-01", $nama, "12345");
        $kasir->login();
        echo "Login berhasil untuk pengguna: " . $kasir->getNama() . "\n";
        echo "Peran aktif: " . $kasir->getPeran() . "\n";

        $sesi = true;
        while ($sesi) {
            echo "\n--- MENU KASIR (" . $kasir->getNama() . ") ---\n";
            echo "1. Buat Pesanan Baru\n";
            echo "0. Logout\n";
            $pilih = $this->bacaInt("Pilih aksi [0-1]: ", 0, 1);

            if ($pilih === 1) {
                $this->prosesPemesanan($kasir);
            } else {
                $kasir->logout();
                echo $kasir->getNama() . " telah logout dari sistem.\n";
                $sesi = false;
            }
        }
    }

    private function jalankanSesiManajer(): void {
        echo "Masukkan Nama Manajer (Enter untuk 'Siska'): ";
        $nama = trim((string)fgets(STDIN));
        if ($nama === '') {
            $nama = "Siska";
        }

        $manajer = new Manajer("MNJ-01", $nama, "admin");
        $manajer->login();
        echo "Login berhasil untuk pengguna: " . $manajer->getNama() . "\n";
        echo "Peran aktif: " . $manajer->getPeran() . "\n";

        $sesi = true;
        while ($sesi) {
            echo "\n--- MENU MANAJER (" . $manajer->getNama() . ") ---\n";
            echo "1. Lihat Laporan Penjualan\n";
            echo "0. Logout\n";
            $pilih = $this->bacaInt("Pilih aksi [0-1]: ", 0, 1);

            if ($pilih === 1) {
                $manajer->lihatLaporan();
                $this->tampilkanLaporanPenjualan();
            } else {
                $manajer->logout();
                echo $manajer->getNama() . " telah logout dari sistem.\n";
                $sesi = false;
            }
        }
    }

    private function prosesPemesanan(Kasir $kasir): void {
        $nomorMeja = $this->bacaInt("\nMasukkan Nomor Meja (1-50): ", 1, 50);
        $idPesanan = sprintf("ORD-%03d", $this->urutanPesanan++);
        $pesanan = $kasir->buatPesanan($idPesanan, $nomorMeja);
        echo "Pesanan dibuka -> ID: " . $pesanan->getIdPesanan() . " | Status: " . $pesanan->getStatus() . "\n";

        $kelola = true;
        while ($kelola) {
            echo "\n--- KELOLA KERANJANG (" . $pesanan->getIdPesanan() . " | Meja $nomorMeja) ---\n";
            echo "1. Tambah Item Menu\n";
            echo "2. Lihat / Hapus Item di Keranjang\n";
            echo "3. Lanjut ke Diskon & Pembayaran\n";
            echo "0. Batalkan Pesanan\n";

            $aksi = $this->bacaInt("Pilih aksi [0-3]: ", 0, 3);
            switch ($aksi) {
                case 1:
                    $this->tambahItemKePesanan($pesanan);
                    break;
                case 2:
                    $this->lihatDanHapusItem($pesanan);
                    break;
                case 3:
                    if (empty($pesanan->getItems())) {
                        echo "[!] Keranjang masih kosong. Tambahkan minimal 1 menu.\n";
                    } else {
                        $kelola = false;
                    }
                    break;
                case 0:
                    $pesanan->setStatus(Pesanan::DIBATALKAN);
                    echo "Pesanan " . $pesanan->getIdPesanan() . " dibatalkan.\n";
                    return;
            }
        }

        $this->pilihDiskon($pesanan);
        $pembayaran = $this->prosesPembayaran($pesanan);

        // Kurangi stok master
        foreach ($pesanan->getItems() as $item) {
            foreach ($this->katalogMenu as $master) {
                if ($master->getIdMenu() === $item->getMenu()->getIdMenu()) {
                    $master->kurangiStok($item->getJumlah());
                }
            }
        }

        $pesanan->setPembayaran($pembayaran);
        $pesanan->buatStruk();
        $pesanan->setStatus(Pesanan::SELESAI);

        // Simpan ke storage lokal
        $this->storage->simpanKatalogMenu($this->katalogMenu);
        $this->storage->simpanPesanan($pesanan);

        $this->cetakStrukLengkap($pesanan, $pembayaran);
    }

    private function tambahItemKePesanan(Pesanan $pesanan): void {
        echo "\n--- KATALOG MENU ---\n";
        foreach ($this->katalogMenu as $i => $m) {
            printf("%d. [%s] %-22s | Rp %s | Stok: %d\n",
                $i + 1, $m->getIdMenu(), $m->getNama(), number_format($m->getHarga(), 0, ',', '.'), $m->getStok());
        }
        echo "0. Kembali\n";

        $pilih = $this->bacaInt("Pilih nomor menu: ", 0, count($this->katalogMenu));
        if ($pilih === 0) return;

        $masterMenu = $this->katalogMenu[$pilih - 1];
        $jumlah = $this->bacaInt("Jumlah porsi: ", 1, 999);

        if ($masterMenu instanceof Makanan) {
            echo "Apakah ingin dipanaskan? (+Rp 2.000/porsi) [y/n]: ";
            $jawab = trim((string)fgets(STDIN));
            $dipanaskan = strtolower($jawab) === 'y';
            $menuItem = new Makanan($masterMenu->getIdMenu(), $masterMenu->getNama(), $masterMenu->getHarga(), $masterMenu->getStok(), $dipanaskan);
        } elseif ($masterMenu instanceof Minuman) {
            echo "Pilihan Ukuran:\n1. Regular (Standar)\n2. Large (+Rp 3.000)\n";
            $pUkuran = $this->bacaInt("Pilih ukuran [1-2]: ", 1, 2);
            $ukuran = ($pUkuran === 2) ? "Large" : "Regular";

            echo "Pilihan Suhu:\n1. Dingin (Pakai Es)\n2. Panas / Hangat\n";
            $pSuhu = $this->bacaInt("Pilih suhu [1-2]: ", 1, 2);
            $suhu = ($pSuhu === 2) ? "Panas" : "Dingin";

            $menuItem = new Minuman($masterMenu->getIdMenu(), $masterMenu->getNama(), $masterMenu->getHarga(), $masterMenu->getStok(), $ukuran, $suhu);
        } else {
            $menuItem = $masterMenu;
        }

        echo "Catatan khusus (Enter jika tidak ada): ";
        $catatan = trim((string)fgets(STDIN));

        try {
            $item = new ItemPesanan($menuItem, $jumlah, $catatan);
            $pesanan->tambahItem($item);
            printf("Berhasil ditambahkan: %dx %s (Subtotal: Rp %s)\n",
                $item->getJumlah(), $menuItem->getNama(), number_format($item->hitungSubtotal(), 0, ',', '.'));
        } catch (\Exception $ex) {
            echo "[!] Gagal menambah item: " . $ex->getMessage() . "\n";
        }
    }

    private function lihatDanHapusItem(Pesanan $pesanan): void {
        $items = $pesanan->getItems();
        if (empty($items)) {
            echo "Keranjang pesanan masih kosong.\n";
            return;
        }

        echo "\n--- ISI KERANJANG ---\n";
        foreach ($items as $i => $item) {
            $opsi = "-";
            if ($item->getMenu() instanceof Makanan) {
                $opsi = $item->getMenu()->isDipanaskan() ? "Dipanaskan" : "Biasa";
            } elseif ($item->getMenu() instanceof Minuman) {
                $opsi = $item->getMenu()->getUkuran() . ", " . $item->getMenu()->getSuhu();
            }
            $cat = $item->getCatatan() !== '' ? $item->getCatatan() : '-';
            printf("%d. %dx %-20s (%s) | Catatan: %-12s | Rp %s\n",
                $i + 1, $item->getJumlah(), $item->getMenu()->getNama(), $opsi, $cat, number_format($item->hitungSubtotal(), 0, ',', '.'));
        }
        printf("Subtotal Sementara: Rp %s\n", number_format($pesanan->hitungSubtotal(), 0, ',', '.'));

        $hapusIdx = $this->bacaInt("Masukkan nomor item untuk dihapus (0 untuk kembali): ", 0, count($items));
        if ($hapusIdx > 0) {
            $pesanan->hapusItem($items[$hapusIdx - 1]);
            echo "Item berhasil dihapus dari keranjang.\n";
        }
    }

    private function pilihDiskon(Pesanan $pesanan): void {
        printf("\nSubtotal Kotor: Rp %s\n", number_format($pesanan->hitungSubtotal(), 0, ',', '.'));
        echo "--- PILIH DISKON ---\n";
        echo "1. Tanpa Diskon\n";
        echo "2. Diskon Member (Persentase %)\n";
        echo "3. Diskon Voucher (Nominal Rp)\n";

        $pilih = $this->bacaInt("Pilih diskon [1-3]: ", 1, 3);
        if ($pilih === 1) {
            $pesanan->setDiskon(null);
        } elseif ($pilih === 2) {
            while (true) {
                $persen = (float)$this->bacaDouble("Masukkan persentase diskon (0-100): ", 0, 100);
                try {
                    $pesanan->setDiskon(new DiskonMember($persen));
                    break;
                } catch (\Exception $ex) {
                    echo "[!] " . $ex->getMessage() . "\n";
                }
            }
        } elseif ($pilih === 3) {
            while (true) {
                $nominal = (float)$this->bacaDouble("Masukkan nominal potongan voucher (Rp): ", 0, PHP_FLOAT_MAX);
                try {
                    $pesanan->setDiskon(new DiskonVoucher($nominal));
                    break;
                } catch (\Exception $ex) {
                    echo "[!] " . $ex->getMessage() . "\n";
                }
            }
        }

        $subtotal = $pesanan->hitungSubtotal();
        $total = $pesanan->hitungTotal();
        printf("Potongan Diskon : Rp %s\n", number_format($subtotal - $total, 0, ',', '.'));
        printf("TOTAL TAGIHAN   : Rp %s\n", number_format($total, 0, ',', '.'));
    }

    private function prosesPembayaran(Pesanan $pesanan): \Resto\Model\Pembayaran {
        $totalTagihan = $pesanan->hitungTotal();

        while (true) {
            echo "\n--- METODE PEMBAYARAN ---\n";
            echo "1. Tunai (Cash)\n";
            echo "2. QRIS\n";
            $metode = $this->bacaInt("Pilih metode pembayaran [1-2]: ", 1, 2);

            if ($metode === 1) {
                $uang = (float)$this->bacaDouble("Masukkan uang tunai diterima (Rp): ", 0, PHP_FLOAT_MAX);
                $tunai = new Tunai($uang);
                if ($tunai->prosesPembayaran($totalTagihan)) {
                    printf("Pembayaran Tunai Berhasil! Kembalian: Rp %s\n", number_format($tunai->hitungKembalian(), 0, ',', '.'));
                    return $tunai;
                } else {
                    printf("[!] Uang kurang. Total tagihan adalah Rp %s\n", number_format($totalTagihan, 0, ',', '.'));
                }
            } else {
                echo "Masukkan kode QR (harus diawali 'QRIS-', contoh QRIS-001): ";
                $kode = trim((string)fgets(STDIN));
                $qris = new QRIS($kode);
                if ($qris->prosesPembayaran($totalTagihan)) {
                    echo "Pembayaran QRIS Berhasil!\n";
                    return $qris;
                } else {
                    echo "[!] Kode QR tidak valid. Pastikan diawali 'QRIS-'.\n";
                }
            }
        }
    }

    private function cetakStrukLengkap(Pesanan $pesanan, \Resto\Model\Pembayaran $pembayaran): void {
        echo "\n";
        $struk = $pesanan->getStruk() ?? $pesanan->buatStruk();
        $struk->cetak();

        echo "ID Pesanan : " . $pesanan->getIdPesanan() . "\n";
        echo "No. Meja   : " . $pesanan->getNomorMeja() . "\n";
        echo "Status     : " . $pesanan->getStatus() . "\n";
        echo "----------------------------\n";
        foreach ($pesanan->getItems() as $item) {
            $opsi = "";
            if ($item->getMenu() instanceof Makanan && $item->getMenu()->isDipanaskan()) {
                $opsi = " [Dipanaskan]";
            } elseif ($item->getMenu() instanceof Minuman) {
                $opsi = " [" . $item->getMenu()->getUkuran() . ", " . $item->getMenu()->getSuhu() . "]";
            }
            printf("%dx %-18s Rp %s\n",
                $item->getJumlah(), $item->getMenu()->getNama() . $opsi, number_format($item->hitungSubtotal(), 0, ',', '.'));
            if ($item->getCatatan() !== '') {
                echo "   Catatan: " . $item->getCatatan() . "\n";
            }
        }
        $subtotal = $pesanan->hitungSubtotal();
        $total = $pesanan->hitungTotal();
        echo "----------------------------\n";
        printf("Subtotal   : Rp %s\n", number_format($subtotal, 0, ',', '.'));
        printf("Diskon     : -Rp %s\n", number_format($subtotal - $total, 0, ',', '.'));
        printf("Total Bayar: Rp %s\n", number_format($total, 0, ',', '.'));
        if ($pembayaran instanceof Tunai) {
            printf("Tunai      : Rp %s\n", number_format($pembayaran->getUangDiterima(), 0, ',', '.'));
            printf("Kembalian  : Rp %s\n", number_format($pembayaran->hitungKembalian(), 0, ',', '.'));
        } elseif ($pembayaran instanceof QRIS) {
            echo "QRIS Kode  : " . $pembayaran->getKodeQR() . "\n";
        }
        echo "============================\n";
    }

    private function tampilkanLaporanPenjualan(): void {
        echo "\n=== REKAP LAPORAN TRANSAKSI (LOCAL JSON) ===\n";
        $riwayat = $this->storage->getRiwayatPesanan();
        if (empty($riwayat)) {
            echo "Belum ada transaksi yang selesai pada sesi ini.\n";
            return;
        }
        $totalOmzet = 0.0;
        foreach ($riwayat as $p) {
            printf("- %s | Meja %d | %d item | Total: Rp %s\n",
                $p['idPesanan'], $p['nomorMeja'], count($p['items']), number_format($p['total'], 0, ',', '.'));
            $totalOmzet += $p['total'];
        }
        echo "-------------------------------\n";
        printf("Total Transaksi : %d\n", count($riwayat));
        printf("Total Omzet     : Rp %s\n", number_format($totalOmzet, 0, ',', '.'));
    }

    private function bacaInt(string $prompt, int $min, int $max): int {
        while (true) {
            echo $prompt;
            $line = trim((string)fgets(STDIN));
            if (is_numeric($line)) {
                $val = (int)$line;
                if ($val >= $min && $val <= $max) {
                    return $val;
                }
            }
            printf("[!] Masukkan angka antara %d hingga %d.\n", $min, $max);
        }
    }

    private function bacaDouble(string $prompt, float $min, float $max): float {
        while (true) {
            echo $prompt;
            $line = trim((string)fgets(STDIN));
            if (is_numeric($line)) {
                $val = (float)$line;
                if ($val >= $min && $val <= $max) {
                    return $val;
                }
            }
            echo "[!] Nilai di luar batas yang diperbolehkan.\n";
        }
    }
}

// Entry Point jika dijalankan via CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $app = new MainCLI();
    $app->run();
}
