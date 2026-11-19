package com.resto.model;

public class Makanan extends Menu {
    private boolean pedas;

    public Makanan(String idMenu, String nama, double harga, int stok, boolean pedas) {
        super(idMenu, nama, harga, stok);
        this.pedas = pedas;
    }

    public boolean isPedas() {
        return pedas;
    }

    public void setPedas(boolean pedas) {
        this.pedas = pedas;
    }

    @Override
    public double hitungHarga() {
        return getHarga();
    }
}