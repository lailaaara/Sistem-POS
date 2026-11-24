<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Pengguna.php';

class Kasir extends Pengguna {
    public function buatPesanan(?string $idPesanan = null, int $nomorMeja = 1): Pesanan {
        $id = $idPesanan ?? "ORD-NEW";
        return new Pesanan($id, $nomorMeja);
    }

    public function getPeran(): string {
        return "Kasir";
    }
}
