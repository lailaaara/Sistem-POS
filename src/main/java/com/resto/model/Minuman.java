package com.resto.model;

public class Minuman extends Menu {
    private boolean dingin;

    public Minuman(String idMenu, String nama, double harga, int stok, boolean dingin) {
        super(idMenu, nama, harga, stok);
        this.dingin = dingin;
    }

    public boolean isDingin() {
        return dingin;
    }

    public void setDingin(boolean dingin) {
        this.dingin = dingin;
    }

    @Override
    public double hitungHarga() {
        return getHarga();
    }
}