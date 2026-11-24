<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Menu.php';

class Makanan extends Menu {
    private bool $dipanaskan;

    public function __construct(string $idMenu = "", string $nama = "", float $harga = 0.0, int $stok = 0, bool $dipanaskan = false) {
        parent::__construct($idMenu, $nama, $harga, $stok);
        $this->dipanaskan = $dipanaskan;
    }

    public function isDipanaskan(): bool {
        return $this->dipanaskan;
    }

    public function setDipanaskan(bool $dipanaskan): void {
        $this->dipanaskan = $dipanaskan;
    }

    public function hitungHarga(): float {
        return $this->dipanaskan ? $this->getHarga() + 2000.0 : $this->getHarga();
    }
}
