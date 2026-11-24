<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/Pembayaran.php';

class QRIS extends Pembayaran {
    private string $kodeQR;

    public function __construct(string $kodeQR = "") {
        parent::__construct();
        $this->kodeQR = $kodeQR;
    }

    public function getKodeQR(): string {
        return $this->kodeQR;
    }

    public function setKodeQR(string $kodeQR): void {
        $this->kodeQR = $kodeQR;
    }

    public function prosesPembayaran(float $total): bool {
        if ($total < 0 || trim($this->kodeQR) === '') {
            return false;
        }
        if (str_starts_with($this->kodeQR, 'QRIS-')) {
            $this->jumlahBayar = $total;
            return true;
        }
        return false;
    }
}
