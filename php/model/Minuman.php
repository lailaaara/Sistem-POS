<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Menu.php';

class Minuman extends Menu {
    private string $ukuran;
    private string $suhu;

    public function __construct(string $idMenu = "", string $nama = "", float $harga = 0.0, int $stok = 0, string $ukuran = "Regular", string $suhu = "Dingin") {
        parent::__construct($idMenu, $nama, $harga, $stok);
        $this->ukuran = $ukuran;
        $this->suhu = $suhu;
    }

    public function getUkuran(): string {
        return $this->ukuran;
    }

    public function setUkuran(string $ukuran): void {
        $this->ukuran = $ukuran;
    }

    public function getSuhu(): string {
        return $this->suhu;
    }

    public function setSuhu(string $suhu): void {
        $this->suhu = $suhu;
    }

    public function hitungHarga(): float {
        $totalHarga = $this->getHarga();
        if (strcasecmp($this->ukuran, "Large") === 0) {
            $totalHarga += 3000.0;
        }
        return $totalHarga;
    }
}
