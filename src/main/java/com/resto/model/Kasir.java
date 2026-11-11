package com.resto.model;

public class Kasir extends Pengguna {

    public Kasir(String idPengguna, String nama, String password) {
        super(idPengguna, nama, password);
    }

    // Sesuai diagram: +buatPesanan() : Pesanan
    public Pesanan buatPesanan() {
        System.out.println("Kasir [" + getNama() + "] membuka form pesanan baru...");
        // Nomor meja default 1 karena Pesanan memvalidasi nomorMeja > 0
        return new Pesanan("ORD-NEW", 1);
    }

    @Override
    public String getPeran() {
        return "Kasir";
    }
}