package com.resto.model;

public class Kasir extends Pengguna {

    public Kasir(String idPengguna, String nama, String password) {
        super(idPengguna, nama, password);
    }

    // Sesuai diagram: +buatPesanan() : Pesanan
    public Pesanan buatPesanan() {
        System.out.println("Kasir [" + getNama() + "] membuka form pesanan baru...");
        // Mengembalikan objek Pesanan kosong (mengikuti constructor di Main)
        return new Pesanan("ORD-NEW", 0); 
    }

    @Override
    public String getPeran() {
        return "Kasir";
    }
}