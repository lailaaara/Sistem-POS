<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Diskon.php';

class DiskonMember implements Diskon {
    private float $persen;

    public function __construct(float $persen = 0.0) {
        $this->setPersen($persen);
    }

    public function getPersen(): float {
        return $this->persen;
    }

    public function setPersen(float $persen): void {
        if ($persen < 0 || $persen > 100) {
            throw new \InvalidArgumentException("Persentase diskon member harus berada di antara 0 hingga 100.");
        }
        $this->persen = $persen;
    }

    public function hitungDiskon(float $total): float {
        if ($total < 0) {
            throw new \InvalidArgumentException("Total tagihan tidak boleh bernilai negatif.");
        }
        return $total * ($this->persen / 100.0);
    }
}
