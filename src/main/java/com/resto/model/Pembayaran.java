package com.resto.model;

/**
 * Kelas abstrak dasar yang merepresentasikan mekanisme pembayaran transaksi di restoran.
 * Menyediakan struktur dasar untuk mencatat jumlah pembayaran dan mendefinisikan
 * kontrak proses pembayaran melalui metode abstrak.
 */
public abstract class Pembayaran {
    protected double jumlahBayar;

    /**
     * Konstruktor default Pembayaran.
     */
    public Pembayaran() {
    }

    /**
     * Konstruktor berparameter untuk inisialisasi Pembayaran dengan jumlah bayar.
     *
     * @param jumlahBayar Jumlah uang yang dibayarkan
     */
    public Pembayaran(double jumlahBayar) {
        this.jumlahBayar = jumlahBayar;
    }

    // --- Getter dan Setter ---

    public double getJumlahBayar() {
        return jumlahBayar;
    }

    /**
     * Metode abstrak untuk memproses transaksi pembayaran berdasarkan total tagihan.
     * Di-override oleh kelas turunan (seperti Tunai dan QRIS) sesuai mekanisme pembayaran masing-masing.
     *
     * @param total Total tagihan pesanan yang harus dibayar
     * @return true jika pembayaran berhasil diproses, false jika gagal atau tidak valid
     */
    public abstract boolean prosesPembayaran(double total);

    @Override
    public String toString() {
        return "Pembayaran{" +
                "jumlahBayar=" + jumlahBayar +
                '}';
    }
}

