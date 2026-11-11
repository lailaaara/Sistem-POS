package com.resto.model;

import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Objects;
import java.util.UUID;

/**
 * Keranjang pesanan restoran yang memuat daftar ItemPesanan, status,
 * nomor meja, dan skema diskon.
 */
public class Pesanan {
    public static final String MENUNGGU_PEMBAYARAN = "MENUNGGU_PEMBAYARAN";
    public static final String SELESAI = "SELESAI";
    public static final String DIBATALKAN = "DIBATALKAN";

    private final String idPesanan;
    private final Date tanggal;
    private String status;
    private final int nomorMeja;
    private final List<ItemPesanan> items = new ArrayList<>();
    private Diskon diskon; // Relasi UML: memakai 0..1 Diskon
    private Pembayaran pembayaran; // Relasi UML: dibayar dengan 0..1 Pembayaran

    public Pesanan(String idPesanan, int nomorMeja) {
        this(idPesanan, new Date(), MENUNGGU_PEMBAYARAN, nomorMeja);
    }

    public Pesanan(int nomorMeja) {
        this(UUID.randomUUID().toString(), new Date(), MENUNGGU_PEMBAYARAN, nomorMeja);
    }

    public Pesanan(String idPesanan, Date tanggal, String status, int nomorMeja) {
        if (idPesanan == null || idPesanan.isBlank()) {
            throw new IllegalArgumentException("ID pesanan tidak boleh kosong.");
        }
        if (nomorMeja <= 0) {
            throw new IllegalArgumentException("Nomor meja harus lebih dari nol.");
        }
        this.idPesanan = idPesanan;
        this.tanggal = new Date(Objects.requireNonNull(tanggal, "Tanggal tidak boleh null.").getTime());
        setStatus(status);
        this.nomorMeja = nomorMeja;
    }

    public String getIdPesanan() {
        return idPesanan;
    }

    public Date getTanggal() {
        return new Date(tanggal.getTime());
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        if (!MENUNGGU_PEMBAYARAN.equals(status)
                && !SELESAI.equals(status)
                && !DIBATALKAN.equals(status)) {
            throw new IllegalArgumentException("Status pesanan tidak dikenal: " + status);
        }
        if (this.status != null
                && !this.status.equals(status)
                && !MENUNGGU_PEMBAYARAN.equals(this.status)) {
            throw new IllegalStateException("Status pesanan yang sudah final tidak dapat diubah.");
        }
        this.status = status;
    }

    public int getNomorMeja() {
        return nomorMeja;
    }

    public List<ItemPesanan> getItems() {
        return List.copyOf(items);
    }

    public Diskon getDiskon() {
        return diskon;
    }

    public void setDiskon(Diskon diskon) {
        pastikanMasihDapatDiubah();
        this.diskon = diskon;
    }

    public Pembayaran getPembayaran() {
        return pembayaran;
    }

    public void setPembayaran(Pembayaran pembayaran) {
        pastikanMasihDapatDiubah();
        this.pembayaran = pembayaran;
    }

    public void tambahItem(ItemPesanan item) {
        Objects.requireNonNull(item, "Item pesanan tidak boleh null.");
        pastikanMasihDapatDiubah();

        if (item.getMenu() == null) {
            throw new IllegalArgumentException("Item tidak memiliki menu valid.");
        }

        // Hitung akumulasi item dengan menu yang sama yang sudah ada di pesanan
        long jumlahTerpesan = items.stream()
                .filter(existing -> menuSama(existing.getMenu(), item.getMenu()))
                .mapToLong(ItemPesanan::getJumlah)
                .sum();

        if (item.getJumlah() > item.getMenu().getStok() - jumlahTerpesan) {
            throw new IllegalArgumentException(
                    "Stok tidak mencukupi untuk menu: " + item.getMenu().getNama());
        }

        items.add(item);
    }

    public void hapusItem(ItemPesanan item) {
        Objects.requireNonNull(item, "Item pesanan tidak boleh null.");
        pastikanMasihDapatDiubah();

        // Mencocokkan instance langsung atau kesamaan Menu dan Catatan
        boolean terhapus = items.removeIf(existing ->
                existing == item ||
                (menuSama(existing.getMenu(), item.getMenu()) &&
                 Objects.equals(existing.getCatatan(), item.getCatatan()) &&
                 existing.getJumlah() == item.getJumlah())
        );

        if (!terhapus) {
            throw new IllegalArgumentException("Item tidak ditemukan dalam pesanan.");
        }
    }

    public double hitungSubtotal() {
        return items.stream()
                .mapToDouble(ItemPesanan::hitungSubtotal)
                .sum();
    }

    public double hitungTotal() {
        double subtotal = hitungSubtotal();
        if (diskon != null) {
            double nilaiPotongan = diskon.hitungDiskon(subtotal);
            return Math.max(0.0, subtotal - nilaiPotongan);
        }
        return subtotal;
    }

    private void pastikanMasihDapatDiubah() {
        if (!MENUNGGU_PEMBAYARAN.equals(status)) {
            throw new IllegalStateException("Pesanan hanya dapat diubah saat menunggu pembayaran.");
        }
    }

    private boolean menuSama(Menu menuPertama, Menu menuKedua) {
        if (menuPertama == null || menuKedua == null) {
            return false;
        }
        String idMenu = menuPertama.getIdMenu();
        return menuPertama == menuKedua
                || (idMenu != null && idMenu.equals(menuKedua.getIdMenu()));
    }

    @Override
    public String toString() {
        return "Pesanan{" +
                "idPesanan='" + idPesanan + '\'' +
                ", nomorMeja=" + nomorMeja +
                ", status='" + status + '\'' +
                ", jumlahItem=" + items.size() +
                ", total=" + String.format("Rp %,.2f", hitungTotal()) +
                '}';
    }
}