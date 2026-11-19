package com.resto.model;

/**
 * Merepresentasikan entitas Makanan yang merupakan turunan dari {@link Menu}.
 * Sesuai spesifikasi Class Diagram:
 * - Atribut: dipanaskan (boolean)
 * - Method: hitungHarga() (double)
 */
public class Makanan extends Menu {
    private boolean dipanaskan;

    public Makanan() {
        super();
    }

    public Makanan(String idMenu, String nama, double harga, int stok, boolean dipanaskan) {
        super(idMenu, nama, harga, stok);
        this.dipanaskan = dipanaskan;
    }

    public boolean isDipanaskan() {
        return dipanaskan;
    }

    public void setDipanaskan(boolean dipanaskan) {
        this.dipanaskan = dipanaskan;
    }

    @Override
    public double hitungHarga() {
        // Biaya tambahan pemanasan (Rp 2.000) jika opsi dipanaskan aktif
        return dipanaskan ? getHarga() + 2000.0 : getHarga();
    }

    @Override
    public String toString() {
        return "Makanan{" +
                "idMenu='" + getIdMenu() + '\'' +
                ", nama='" + getNama() + '\'' +
                ", harga=" + getHarga() +
                ", stok=" + getStok() +
                ", dipanaskan=" + dipanaskan +
                '}';
    }
}
