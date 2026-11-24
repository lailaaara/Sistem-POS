<?php
declare(strict_types=1);

namespace Resto\Service;

require_once __DIR__ . '/../model/Menu.php';
require_once __DIR__ . '/../model/Makanan.php';
require_once __DIR__ . '/../model/Minuman.php';
require_once __DIR__ . '/../model/Pesanan.php';

use Resto\Model\Menu;
use Resto\Model\Makanan;
use Resto\Model\Minuman;
use Resto\Model\Pesanan;

class Storage {
    private string $menuFile;
    private string $pesananFile;

    public function __construct(?string $dataDir = null) {
        $dir = $dataDir ?? __DIR__ . '/../data';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->menuFile = $dir . '/menu.json';
        $this->pesananFile = $dir . '/pesanan.json';

        if (!file_exists($this->menuFile)) {
            $this->inisialisasiMenuDefault();
        }
        if (!file_exists($this->pesananFile)) {
            file_put_contents($this->pesananFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function inisialisasiMenuDefault(): void {
        $defaultMenu = [
            ['tipe' => 'Makanan', 'id' => 'M01', 'nama' => 'Nasi Goreng Spesial', 'harga' => 25000, 'stok' => 15, 'dipanaskan' => false],
            ['tipe' => 'Makanan', 'id' => 'M02', 'nama' => 'Ayam Bakar Madu', 'harga' => 30000, 'stok' => 12, 'dipanaskan' => false],
            ['tipe' => 'Makanan', 'id' => 'M03', 'nama' => 'Mie Goreng Seafood', 'harga' => 28000, 'stok' => 10, 'dipanaskan' => false],
            ['tipe' => 'Makanan', 'id' => 'M04', 'nama' => 'Soto Ayam Lamongan', 'harga' => 22000, 'stok' => 15, 'dipanaskan' => false],
            ['tipe' => 'Makanan', 'id' => 'M05', 'nama' => 'Sate Ayam (10 Tusuk)', 'harga' => 32000, 'stok' => 8, 'dipanaskan' => false],
            ['tipe' => 'Minuman', 'id' => 'D01', 'nama' => 'Es Teh Manis', 'harga' => 5000, 'stok' => 30, 'ukuran' => 'Regular', 'suhu' => 'Dingin'],
            ['tipe' => 'Minuman', 'id' => 'D02', 'nama' => 'Kopi Susu Gula Aren', 'harga' => 18000, 'stok' => 20, 'ukuran' => 'Regular', 'suhu' => 'Dingin'],
            ['tipe' => 'Minuman', 'id' => 'D03', 'nama' => 'Jeruk Peras Hangat', 'harga' => 8000, 'stok' => 25, 'ukuran' => 'Regular', 'suhu' => 'Panas'],
        ];
        file_put_contents($this->menuFile, json_encode($defaultMenu, JSON_PRETTY_PRINT));
    }

    /** @return Menu[] */
    public function getKatalogMenu(): array {
        $raw = json_decode((string)file_get_contents($this->menuFile), true) ?? [];
        $katalog = [];
        foreach ($raw as $item) {
            if ($item['tipe'] === 'Makanan') {
                $katalog[] = new Makanan(
                    $item['id'],
                    $item['nama'],
                    (float)$item['harga'],
                    (int)$item['stok'],
                    (bool)($item['dipanaskan'] ?? false)
                );
            } else {
                $katalog[] = new Minuman(
                    $item['id'],
                    $item['nama'],
                    (float)$item['harga'],
                    (int)$item['stok'],
                    $item['ukuran'] ?? 'Regular',
                    $item['suhu'] ?? 'Dingin'
                );
            }
        }
        return $katalog;
    }

    /** @param Menu[] $katalog */
    public function simpanKatalogMenu(array $katalog): void {
        $data = [];
        foreach ($katalog as $m) {
            if ($m instanceof Makanan) {
                $data[] = [
                    'tipe' => 'Makanan',
                    'id' => $m->getIdMenu(),
                    'nama' => $m->getNama(),
                    'harga' => $m->getHarga(),
                    'stok' => $m->getStok(),
                    'dipanaskan' => $m->isDipanaskan()
                ];
            } elseif ($m instanceof Minuman) {
                $data[] = [
                    'tipe' => 'Minuman',
                    'id' => $m->getIdMenu(),
                    'nama' => $m->getNama(),
                    'harga' => $m->getHarga(),
                    'stok' => $m->getStok(),
                    'ukuran' => $m->getUkuran(),
                    'suhu' => $m->getSuhu()
                ];
            }
        }
        file_put_contents($this->menuFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function simpanPesanan(Pesanan $pesanan): void {
        $raw = json_decode((string)file_get_contents($this->pesananFile), true) ?? [];
        
        $itemData = [];
        foreach ($pesanan->getItems() as $item) {
            $m = $item->getMenu();
            $itemData[] = [
                'idMenu' => $m !== null ? $m->getIdMenu() : '',
                'nama' => $m !== null ? $m->getNama() : '',
                'jumlah' => $item->getJumlah(),
                'catatan' => $item->getCatatan(),
                'subtotal' => $item->hitungSubtotal()
            ];
        }

        $pembayaranTipe = 'Lainnya';
        $kembalian = 0.0;
        if ($pesanan->getPembayaran() !== null) {
            $p = $pesanan->getPembayaran();
            if ($p instanceof \Resto\Model\Tunai) {
                $pembayaranTipe = 'Tunai';
                $kembalian = $p->hitungKembalian();
            } elseif ($p instanceof \Resto\Model\QRIS) {
                $pembayaranTipe = 'QRIS';
            }
        }

        $raw[] = [
            'idPesanan' => $pesanan->getIdPesanan(),
            'nomorMeja' => $pesanan->getNomorMeja(),
            'tanggal' => $pesanan->getTanggal()->format('Y-m-d H:i:s'),
            'status' => $pesanan->getStatus(),
            'subtotal' => $pesanan->hitungSubtotal(),
            'total' => $pesanan->hitungTotal(),
            'pembayaranTipe' => $pembayaranTipe,
            'kembalian' => $kembalian,
            'noStruk' => $pesanan->getStruk() !== null ? $pesanan->getStruk()->getNoStruk() : null,
            'items' => $itemData
        ];

        file_put_contents($this->pesananFile, json_encode($raw, JSON_PRETTY_PRINT));
    }

    public function getRiwayatPesanan(): array {
        return json_decode((string)file_get_contents($this->pesananFile), true) ?? [];
    }
}
