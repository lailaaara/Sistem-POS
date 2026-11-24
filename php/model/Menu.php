<?php
declare(strict_types=1);

namespace Resto\Model;

abstract class Menu {
    private string $idMenu;
    private string $nama;
    private float $harga;
    private int $stok;

    public function __construct(string $idMenu = "", string $nama = "", float $harga = 0.0, int $stok = 0) {
        $this->idMenu = $idMenu;
        $this->nama = $nama;
        $this->harga = $harga;
        $this->stok = $stok;
    }

    public function getIdMenu(): string {
        return $this->idMenu;
    }

    public function setIdMenu(string $idMenu): void {
        $this->idMenu = $idMenu;
    }

    public function getNama(): string {
        return $this->nama;
    }

    public function setNama(string $nama): void {
        $this->nama = $nama;
    }

    public function getHarga(): float {
        return $this->harga;
    }

    public function setHarga(float $harga): void {
        $this->harga = $harga;
    }

    public function getStok(): int {
        return $this->stok;
    }

    public function setStok(int $jumlah): void {
        if ($jumlah < 0) {
            throw new \InvalidArgumentException("Stok tidak boleh bernilai negatif.");
        }
        $this->stok = $jumlah;
    }

    public function kurangiStok(int $jumlah): bool {
        if ($jumlah > 0 && $this->stok >= $jumlah) {
            $this->stok -= $jumlah;
            return true;
        }
        return false;
    }

    public function tambahStok(int $jumlah): void {
        if ($jumlah > 0) {
            $this->stok += $jumlah;
        }
    }

    abstract public function hitungHarga(): float;
}
