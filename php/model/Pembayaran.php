<?php
declare(strict_types=1);

namespace Resto\Model;

abstract class Pembayaran {
    protected float $jumlahBayar = 0.0;

    public function __construct(float $jumlahBayar = 0.0) {
        $this->jumlahBayar = $jumlahBayar;
    }

    public function getJumlahBayar(): float {
        return $this->jumlahBayar;
    }

    abstract public function prosesPembayaran(float $total): bool;
}
