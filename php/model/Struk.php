<?php
declare(strict_types=1);

namespace Resto\Model;

class Struk {
    private string $noStruk;

    public function __construct(string $noStruk) {
        $this->noStruk = $noStruk;
    }

    public function getNoStruk(): string {
        return $this->noStruk;
    }

    public function generateStruk(): string {
        return "============================\n" .
               "       STRUK PEMBAYARAN     \n" .
               " No: " . $this->noStruk . "\n" .
               "============================";
    }

    public function cetak(): void {
        echo "Mencetak struk fisik untuk nomor: " . $this->noStruk . "...\n";
        echo $this->generateStruk() . "\n";
    }
}
