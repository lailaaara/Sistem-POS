package com.resto.model;

/**
 * Kelas konkret yang merepresentasikan metode pembayaran non-tunai melalui QRIS.
 * Merupakan turunan dari kelas abstrak Pembayaran yang mengelola verifikasi
 * kode QR transaksi digital.
 */
public class QRIS extends Pembayaran {
    private String kodeQR;

    /**
     * Konstruktor default QRIS.
     */
    public QRIS() {
        super();
    }

    /**
     * Konstruktor berparameter untuk inisialisasi pembayaran QRIS dengan kode QR.
     *
     * @param kodeQR Kode string QRIS untuk verifikasi pembayaran
     */
    public QRIS(String kodeQR) {
        super();
        this.kodeQR = kodeQR;
    }

    // --- Getter dan Setter ---

    public String getKodeQR() {
        return kodeQR;
    }

    public void setKodeQR(String kodeQR) {
        this.kodeQR = kodeQR;
    }

    /**
     * Memproses transaksi pembayaran QRIS melalui simulasi verifikasi status pembayaran.
     * Jika {@code total <= 0} atau kodeQR kosong/null, pembayaran ditolak.
     * Jika kode valid (misalnya diawali "QRIS-"), pembayaran berhasil dan jumlahBayar dicatat sebesar total.
     * Selain itu, pembayaran ditolak.
     *
     * @param total Total tagihan pesanan yang harus dibayar
     * @return true jika verifikasi kode QR berhasil dan total valid, false jika sebaliknya
     */
    @Override
    public boolean prosesPembayaran(double total) {
        if (total <= 0 || kodeQR == null || kodeQR.trim().isEmpty()) {
            return false;
        }
        if (kodeQR.startsWith("QRIS-")) {
            this.jumlahBayar = total;
            return true;
        }
        return false;
    }

    @Override
    public String toString() {
        return "QRIS{" +
                "jumlahBayar=" + getJumlahBayar() +
                ", kodeQR='" + kodeQR + '\'' +
                '}';
    }
}

