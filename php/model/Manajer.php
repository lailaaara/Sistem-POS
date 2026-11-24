<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Pengguna.php';

class Manajer extends Pengguna {
    public function lihatLaporan(): void {
        echo "Manajer [" . $this->getNama() . "] sedang mengakses laporan penjualan bulanan.\n";
    }

    public function getPeran(): string {
        return "Manajer";
    }
}
