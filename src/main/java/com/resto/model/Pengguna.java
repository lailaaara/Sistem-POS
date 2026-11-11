package com.resto.model;

public abstract class Pengguna {
    // Enkapsulasi: atribut diset private sesuai diagram (-)
    private String idPengguna;
    private String nama;
    private String password;
    
    // Atribut tambahan (opsional) agar logika login/logout jalan
    private boolean isLoggedIn;

    public Pengguna(String idPengguna, String nama, String password) {
        this.idPengguna = idPengguna;
        this.nama = nama;
        this.password = password;
        this.isLoggedIn = false;
    }

    // Method login tanpa parameter sesuai diagram
    public boolean login() {
        this.isLoggedIn = true;
        System.out.println("Login berhasil untuk pengguna: " + this.nama);
        return true;
    }

    public void logout() {
        this.isLoggedIn = false;
        System.out.println(this.nama + " telah logout dari sistem.");
    }

    // Abstract method (polimorfisme) yang wajib di-override subclass
    public abstract String getPeran();

    // Getter untuk diakses class lain
    public String getNama() {
        return nama;
    }
}