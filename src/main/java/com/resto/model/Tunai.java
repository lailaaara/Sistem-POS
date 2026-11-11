package com.resto.model;

/**
 * Kelas konkret yang merepresentasikan metode pembayaran tunai (cash).
 * Merupakan turunan dari kelas abstrak Pembayaran yang mengelola validasi
 * kecukupan uang tunai yang diterima dan perhitungan kembalian.
 */
public class Tunai extends Pembayaran {
    private double uangDiterima;

    /**
     * Konstruktor default Tunai.
     */
    public Tunai() {
        super();
    }

    /**
     * Konstruktor berparameter untuk inisialisasi pembayaran tunai.
     *
     * @param uangDiterima Jumlah uang tunai yang diterima dari pelanggan
     */
    public Tunai(double uangDiterima) {
        super();
        if (uangDiterima < 0) {
            throw new IllegalArgumentException("Uang diterima tidak boleh bernilai negatif.");
        }
        this.uangDiterima = uangDiterima;
    }

    // --- Getter dan Setter ---

    public double getUangDiterima() {
        return uangDiterima;
    }

    /**
     * Menetapkan jumlah uang tunai yang diterima dari pelanggan.
     *
     * @param uangDiterima Jumlah uang tunai yang diterima
     * @throws IllegalArgumentException jika uangDiterima bernilai negatif
     */
    public void setUangDiterima(double uangDiterima) {
        if (uangDiterima < 0) {
            throw new IllegalArgumentException("Uang diterima tidak boleh bernilai negatif.");
        }
        this.uangDiterima = uangDiterima;
    }

    /**
     * Memproses transaksi pembayaran tunai dengan memvalidasi kecukupan uang yang diterima.
     * Jika {@code total <= 0}, pembayaran ditolak.
     * Jika {@code uangDiterima >= total}, pembayaran berhasil dan jumlahBayar dicatat sebesar total.
     * Selain itu, pembayaran ditolak.
     *
     * @param total Total tagihan pesanan yang harus dibayar
     * @return true jika pembayaran berhasil, false jika uang tidak cukup atau total tidak valid
     */
    @Override
    public boolean prosesPembayaran(double total) {
        if (total <= 0) {
            return false;
        }
        if (this.uangDiterima >= total) {
            this.jumlahBayar = total;
            return true;
        }
        return false;
    }

    /**
     * Menghitung uang kembalian untuk pelanggan.
     * Uang diterima dikurangi jumlah bayar.
     *
     * @return Jumlah uang kembalian, atau 0 jika pembayaran belum berhasil
     */
    public double hitungKembalian() {
        if (this.jumlahBayar <= 0 || this.uangDiterima < this.jumlahBayar) {
            return 0.0;
        }
        return this.uangDiterima - this.jumlahBayar;
    }

    @Override
    public String toString() {
        return "Tunai{" +
                "jumlahBayar=" + getJumlahBayar() +
                ", uangDiterima=" + uangDiterima +
                ", kembalian=" + hitungKembalian() +
                '}';
    }
}

