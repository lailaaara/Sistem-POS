<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Menu.php';

class ItemPesanan {
    private ?Menu $menu = null;
    private int $jumlah = 0;
    private string $catatan = "";

    public function __construct(?Menu $menu = null, int $jumlah = 1, string $catatan = "") {
        if ($menu !== null) {
            $this->setMenu($menu);
        }
        if ($jumlah > 0) {
            $this->setJumlah($jumlah);
        }
        $this->setCatatan($catatan);
    }

    public function getMenu(): ?Menu {
        return $this->menu;
    }

    public function setMenu(Menu $menu): void {
        $this->menu = $menu;
    }

    public function getJumlah(): int {
        return $this->jumlah;
    }

    public function setJumlah(int $jumlah): void {
        if ($jumlah <= 0) {
            throw new \InvalidArgumentException("Jumlah item pesanan harus lebih dari 0.");
        }
        $this->jumlah = $jumlah;
    }

    public function getCatatan(): string {
        return $this->catatan;
    }

    public function setCatatan(string $catatan): void {
        $this->catatan = $catatan;
    }

    public function hitungSubtotal(): float {
        if ($this->menu === null) {
            throw new \RuntimeException("Menu belum ditentukan untuk item pesanan ini.");
        }
        return $this->menu->hitungHarga() * $this->jumlah;
    }
}
