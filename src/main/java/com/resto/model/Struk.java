package com.resto.model;

public class Struk {
    private String noStruk;

    public Struk(String noStruk) {
        this.noStruk = noStruk;
    }

    // Sesuai diagram: +generateStruk() : String
    public String generateStruk() {
        return "============================\n" +
               "       STRUK PEMBAYARAN     \n" +
               " No: " + noStruk + "\n" +
               "============================";
    }

    // Sesuai diagram: +cetak() : void
    public void cetak() {
        System.out.println("Mencetak struk fisik untuk nomor: " + noStruk + "...");
        System.out.println(generateStruk()); // Memanggil method internal
    }
}