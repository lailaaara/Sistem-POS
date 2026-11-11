package com.resto.model;

public class Manajer extends Pengguna {

    public Manajer(String idPengguna, String nama, String password) {
        super(idPengguna, nama, password);
    }

    // Sesuai diagram: +lihatLaporan() : void
    public void lihatLaporan() {
        System.out.println("Manajer [" + getNama() + "] sedang mengakses laporan penjualan bulanan.");
    }

    @Override
    public String getPeran() {
        return "Manajer";
    }
}