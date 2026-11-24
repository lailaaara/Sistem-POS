<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Diskon.php';

class DiskonVoucher implements Diskon {
    private float $nominalPotongan;

    public function __construct(float $nominalPotongan = 0.0) {
        $this->setNominalPotongan($nominalPotongan);
    }

    public function getNominalPotongan(): float {
        return $this->nominalPotongan;
    }

    public function setNominalPotongan(float $nominalPotongan): void {
        if ($nominalPotongan < 0) {
            throw new \InvalidArgumentException("Nominal potongan voucher tidak boleh bernilai negatif.");
        }
        $this->nominalPotongan = $nominalPotongan;
    }

    public function hitungDiskon(float $total): float {
        if ($total < 0) {
            throw new \InvalidArgumentException("Total tagihan tidak boleh bernilai negatif.");
        }
        return min($this->nominalPotongan, $total);
    }
}
