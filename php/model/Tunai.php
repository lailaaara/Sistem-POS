<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Pembayaran.php';

class Tunai extends Pembayaran {
    private float $uangDiterima;

    public function __construct(float $uangDiterima = 0.0) {
        parent::__construct();
        $this->setUangDiterima($uangDiterima);
    }

    public function getUangDiterima(): float {
        return $this->uangDiterima;
    }

    public function setUangDiterima(float $uangDiterima): void {
        if ($uangDiterima < 0) {
            throw new \InvalidArgumentException("Uang diterima tidak boleh bernilai negatif.");
        }
        $this->uangDiterima = $uangDiterima;
    }

    public function prosesPembayaran(float $total): bool {
        if ($total < 0) {
            return false;
        }
        if ($this->uangDiterima >= $total) {
            $this->jumlahBayar = $total;
            return true;
        }
        return false;
    }

    public function hitungKembalian(): float {
        if ($this->jumlahBayar < 0 || $this->uangDiterima < $this->jumlahBayar) {
            return 0.0;
        }
        return $this->uangDiterima - $this->jumlahBayar;
    }
}
