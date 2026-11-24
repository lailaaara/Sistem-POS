<?php
declare(strict_types=1);

namespace Resto\Model;

require_once __DIR__ . '/ItemPesanan.php';
require_once __DIR__ . '/Diskon.php';
require_once __DIR__ . '/Pembayaran.php';
require_once __DIR__ . '/Struk.php';

class Pesanan {
    public const MENUNGGU_PEMBAYARAN = "MENUNGGU_PEMBAYARAN";
    public const SELESAI = "SELESAI";
    public const DIBATALKAN = "DIBATALKAN";

    private string $idPesanan;
    private \DateTime $tanggal;
    private string $status;
    private int $nomorMeja;

    /** @var ItemPesanan[] */
    private array $items = [];
    private ?Diskon $diskon = null;
    private ?Pembayaran $pembayaran = null;
    private ?Struk $struk = null;

    public function __construct(string $idPesanan, int $nomorMeja, ?\DateTime $tanggal = null, string $status = self::MENUNGGU_PEMBAYARAN) {
        if (trim($idPesanan) === '') {
            throw new \InvalidArgumentException("ID pesanan tidak boleh kosong.");
        }
        if ($nomorMeja <= 0) {
            throw new \InvalidArgumentException("Nomor meja harus lebih dari nol.");
        }
        $this->idPesanan = $idPesanan;
        $this->nomorMeja = $nomorMeja;
        $this->tanggal = $tanggal ?? new \DateTime();
        $this->status = $status;
    }

    public function getIdPesanan(): string {
        return $this->idPesanan;
    }

    public function getTanggal(): \DateTime {
        return clone $this->tanggal;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function setStatus(string $status): void {
        if (!in_array($status, [self::MENUNGGU_PEMBAYARAN, self::SELESAI, self::DIBATALKAN], true)) {
            throw new \InvalidArgumentException("Status pesanan tidak dikenal: " . $status);
        }
        if ($status === self::SELESAI && empty($this->items)) {
            throw new \RuntimeException("Pesanan tidak dapat diselesaikan karena belum memiliki item (minimal 1 menu).");
        }
        if ($this->status !== self::MENUNGGU_PEMBAYARAN && $this->status !== $status) {
            throw new \RuntimeException("Status pesanan yang sudah final tidak dapat diubah.");
        }
        $this->status = $status;
    }

    public function getNomorMeja(): int {
        return $this->nomorMeja;
    }

    /** @return ItemPesanan[] */
    public function getItems(): array {
        return $this->items;
    }

    public function getDiskon(): ?Diskon {
        return $this->diskon;
    }

    public function setDiskon(?Diskon $diskon): void {
        $this->pastikanMasihDapatDiubah();
        $this->diskon = $diskon;
    }

    public function getPembayaran(): ?Pembayaran {
        return $this->pembayaran;
    }

    public function setPembayaran(Pembayaran $pembayaran): void {
        $this->pastikanMasihDapatDiubah();
        if (empty($this->items)) {
            throw new \RuntimeException("Pesanan tidak dapat diproses karena keranjang masih kosong (minimal 1 menu).");
        }
        $this->pembayaran = $pembayaran;
    }

    public function getStruk(): ?Struk {
        return $this->struk;
    }

    public function setStruk(Struk $struk): void {
        $this->struk = $struk;
    }

    public function buatStruk(): Struk {
        $this->struk = new Struk("STRK-" . $this->idPesanan);
        return $this->struk;
    }

    public function tambahItem(ItemPesanan $item): void {
        $this->pastikanMasihDapatDiubah();
        if ($item->getMenu() === null) {
            throw new \InvalidArgumentException("Item tidak memiliki menu valid.");
        }

        // Cek akumulasi stok
        $jumlahTerpesan = 0;
        foreach ($this->items as $existing) {
            if ($existing->getMenu() !== null && $existing->getMenu()->getIdMenu() === $item->getMenu()->getIdMenu()) {
                $jumlahTerpesan += $existing->getJumlah();
            }
        }

        if ($item->getJumlah() > ($item->getMenu()->getStok() - $jumlahTerpesan)) {
            throw new \InvalidArgumentException("Stok tidak mencukupi untuk menu: " . $item->getMenu()->getNama());
        }

        $this->items[] = $item;
    }

    public function hapusItem(ItemPesanan $item): void {
        $this->pastikanMasihDapatDiubah();
        $index = array_search($item, $this->items, true);
        if ($index === false) {
            throw new \InvalidArgumentException("Item tidak ditemukan dalam pesanan.");
        }
        array_splice($this->items, (int)$index, 1);
    }

    public function hitungSubtotal(): float {
        $subtotal = 0.0;
        foreach ($this->items as $item) {
            $subtotal += $item->hitungSubtotal();
        }
        return $subtotal;
    }

    public function hitungTotal(): float {
        $subtotal = $this->hitungSubtotal();
        if ($this->diskon !== null) {
            $potongan = $this->diskon->hitungDiskon($subtotal);
            return max(0.0, $subtotal - $potongan);
        }
        return $subtotal;
    }

    private function pastikanMasihDapatDiubah(): void {
        if ($this->status !== self::MENUNGGU_PEMBAYARAN) {
            throw new \RuntimeException("Pesanan hanya dapat diubah saat menunggu pembayaran.");
        }
    }
}
