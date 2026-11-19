package com.resto.model;

/**
 * Merepresentasikan entitas Minuman yang merupakan turunan dari {@link Menu}.
 * Sesuai spesifikasi Class Diagram:
 * - Atribut: ukuran (String), suhu (String)
 * - Method: hitungHarga() (double)
 */
public class Minuman extends Menu {
    private String ukuran;
    private String suhu;

    public Minuman() {
        super();
        this.ukuran = "Regular";
        this.suhu = "Dingin";
    }

    public Minuman(String idMenu, String nama, double harga, int stok, String ukuran, String suhu) {
        super(idMenu, nama, harga, stok);
        this.ukuran = ukuran;
        this.suhu = suhu;
    }

    public String getUkuran() {
        return ukuran;
    }

    public void setUkuran(String ukuran) {
        this.ukuran = ukuran;
    }

    public String getSuhu() {
        return suhu;
    }

    public void setSuhu(String suhu) {
        this.suhu = suhu;
    }

    @Override
    public double hitungHarga() {
        // Biaya tambahan jika ukuran Large (Rp 3.000)
        double totalHarga = getHarga();
        if (ukuran != null && ukuran.equalsIgnoreCase("Large")) {
            totalHarga += 3000.0;
        }
        return totalHarga;
    }

    @Override
    public String toString() {
        return "Minuman{" +
                "idMenu='" + getIdMenu() + '\'' +
                ", nama='" + getNama() + '\'' +
                ", harga=" + getHarga() +
                ", stok=" + getStok() +
                ", ukuran='" + ukuran + '\'' +
                ", suhu='" + suhu + '\'' +
                '}';
    }
}
