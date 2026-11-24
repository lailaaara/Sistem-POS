<?php
declare(strict_types=1);

namespace Resto\Model;

interface Diskon {
    public function hitungDiskon(float $total): float;
}
