package com.resto;

import com.resto.model.DiskonMember;
import com.resto.model.DiskonVoucher;
import com.resto.model.ItemPesanan;
import com.resto.model.Kasir;
import com.resto.model.Makanan;
import com.resto.model.Manajer;
import com.resto.model.Menu;
import com.resto.model.Pembayaran;
import com.resto.model.Pesanan;
import com.resto.model.QRIS;
import com.resto.model.Struk;
import com.resto.model.Tunai;

import java.util.ArrayList;
import java.util.List;
import java.util.Scanner;

/**
 * Titik masuk utama (Entry Point) aplikasi Console Interaktif Sistem POS Restoran.
 * Memungkinkan pengguna menginput pesanan, diskon, dan pembayaran secara manual lewat terminal.
 */
public class Main {

    private static final Scanner scanner = new Scanner(System.in);
    private static final List<Menu> katalogMenu = new ArrayList<>();
    private static final List<Pesanan> riwayatPesanan = new ArrayList<>();
    private static int urutanPesanan = 1;

    public static void main(String[] args) {
        inisialisasiKatalog();

        boolean aplikasiBerjalan = true;
        while (aplikasiBerjalan) {
            System.out.println("\n====================================================");
            System.out.println("       SISTEM PEMESANAN & PEMBAYARAN RESTORAN       ");
            System.out.println("====================================================");
            System.out.println("1. Login sebagai Kasir");
            System.out.println("2. Login sebagai Manajer");
            System.out.println("0. Keluar Aplikasi");
            System.out.println("----------------------------------------------------");

            int pilihan = bacaInt("Pilih peran [0-2]: ", 0, 2);
            switch (pilihan) {
                case 1 -> jalankanSesiKasir();
                case 2 -> jalankanSesiManajer();
                case 0 -> {
                    aplikasiBerjalan = false;
                    System.out.println("Terima kasih. Program ditutup.");
                }
            }
        }
    }

    private static void inisialisasiKatalog() {
        katalogMenu.add(new Makanan("M01", "Nasi Goreng Spesial", 25000, 15, false));
        katalogMenu.add(new Makanan("M02", "Ayam Bakar Madu", 30000, 12, false));
        katalogMenu.add(new Makanan("M03", "Mie Goreng Seafood", 28000, 10, false));
        katalogMenu.add(new Makanan("M04", "Soto Ayam Lamongan", 22000, 15, false));
        katalogMenu.add(new Makanan("M05", "Sate Ayam (10 Tusuk)", 32000, 8, false));
    }

    private static void jalankanSesiKasir() {
        System.out.print("Masukkan Nama Kasir (Enter untuk 'Budi'): ");
        String nama = scanner.nextLine().trim();
        if (nama.isEmpty()) {
            nama = "Budi";
        }

        Kasir kasir = new Kasir("KSR-01", nama, "12345");
        kasir.login();
        System.out.println("Peran aktif: " + kasir.getPeran());

        boolean sesiAktif = true;
        while (sesiAktif) {
            System.out.println("\n--- MENU KASIR (" + kasir.getNama() + ") ---");
            System.out.println("1. Buat Pesanan Baru");
            System.out.println("0. Logout");
            int pilih = bacaInt("Pilih aksi [0-1]: ", 0, 1);

            if (pilih == 1) {
                prosesPemesanan();
            } else {
                kasir.logout();
                sesiAktif = false;
            }
        }
    }

    private static void jalankanSesiManajer() {
        System.out.print("Masukkan Nama Manajer (Enter untuk 'Siska'): ");
        String nama = scanner.nextLine().trim();
        if (nama.isEmpty()) {
            nama = "Siska";
        }

        Manajer manajer = new Manajer("MNJ-01", nama, "admin");
        manajer.login();
        System.out.println("Peran aktif: " + manajer.getPeran());

        boolean sesiAktif = true;
        while (sesiAktif) {
            System.out.println("\n--- MENU MANAJER (" + manajer.getNama() + ") ---");
            System.out.println("1. Lihat Laporan Penjualan");
            System.out.println("0. Logout");
            int pilih = bacaInt("Pilih aksi [0-1]: ", 0, 1);

            if (pilih == 1) {
                manajer.lihatLaporan();
                tampilkanLaporanPenjualan();
            } else {
                manajer.logout();
                sesiAktif = false;
            }
        }
    }

    private static void prosesPemesanan() {
        int nomorMeja = bacaInt("\nMasukkan Nomor Meja (1-50): ", 1, 50);
        String idPesanan = String.format("ORD-%03d", urutanPesanan++);
        Pesanan pesanan = new Pesanan(idPesanan, nomorMeja);
        System.out.println("Pesanan dibuka -> ID: " + pesanan.getIdPesanan() + " | Status: " + pesanan.getStatus());

        boolean kelolaKeranjang = true;
        while (kelolaKeranjang) {
            System.out.println("\n--- KELOLA KERANJANG (" + pesanan.getIdPesanan() + " | Meja " + nomorMeja + ") ---");
            System.out.println("1. Tambah Item Menu");
            System.out.println("2. Lihat / Hapus Item di Keranjang");
            System.out.println("3. Lanjut ke Diskon & Pembayaran");
            System.out.println("0. Batalkan Pesanan");

            int aksi = bacaInt("Pilih aksi [0-3]: ", 0, 3);
            switch (aksi) {
                case 1 -> tambahItemKePesanan(pesanan);
                case 2 -> lihatDanHapusItem(pesanan);
                case 3 -> {
                    if (pesanan.getItems().isEmpty()) {
                        System.out.println("[!] Keranjang masih kosong. Tambahkan minimal 1 menu.");
                    } else {
                        kelolaKeranjang = false;
                    }
                }
                case 0 -> {
                    pesanan.setStatus(Pesanan.DIBATALKAN);
                    System.out.println("Pesanan " + pesanan.getIdPesanan() + " dibatalkan.");
                    return;
                }
            }
        }

        pilihDiskon(pesanan);
        Pembayaran pembayaran = prosesPembayaran(pesanan);

        // Kurangi stok master setelah pembayaran berhasil
        for (ItemPesanan item : pesanan.getItems()) {
            for (Menu master : katalogMenu) {
                if (master.getIdMenu().equals(item.getMenu().getIdMenu())) {
                    master.kurangiStok(item.getJumlah());
                }
            }
        }

        pesanan.setPembayaran(pembayaran);
        pesanan.setStatus(Pesanan.SELESAI);
        riwayatPesanan.add(pesanan);

        cetakStrukLengkap(pesanan, pembayaran);
    }

    private static void tambahItemKePesanan(Pesanan pesanan) {
        System.out.println("\n--- KATALOG MENU ---");
        for (int i = 0; i < katalogMenu.size(); i++) {
            Menu m = katalogMenu.get(i);
            System.out.printf("%d. [%s] %-22s | Rp %,.0f | Stok: %d\n",
                    i + 1, m.getIdMenu(), m.getNama(), m.getHarga(), m.getStok());
        }
        System.out.println("0. Kembali");

        int pilihMenu = bacaInt("Pilih nomor menu: ", 0, katalogMenu.size());
        if (pilihMenu == 0) {
            return;
        }

        Menu masterMenu = katalogMenu.get(pilihMenu - 1);
        int jumlah = bacaInt("Jumlah porsi: ", 1, 999);

        System.out.print("Apakah ingin dipanaskan? (+Rp 2.000/porsi) [y/n]: ");
        String jawabPanas = scanner.nextLine().trim();
        boolean dipanaskan = jawabPanas.equalsIgnoreCase("y");

        System.out.print("Catatan khusus (Enter jika tidak ada): ");
        String catatan = scanner.nextLine().trim();

        Menu menuItem = new Makanan(
                masterMenu.getIdMenu(),
                masterMenu.getNama(),
                masterMenu.getHarga(),
                masterMenu.getStok(),
                dipanaskan
        );

        try {
            ItemPesanan item = new ItemPesanan(menuItem, jumlah, catatan);
            pesanan.tambahItem(item);
            System.out.printf("Berhasil ditambahkan: %dx %s (Subtotal: Rp %,.0f)\n",
                    item.getJumlah(), menuItem.getNama(), item.hitungSubtotal());
        } catch (IllegalArgumentException | IllegalStateException ex) {
            System.out.println("[!] Gagal menambah item: " + ex.getMessage());
        }
    }

    private static void lihatDanHapusItem(Pesanan pesanan) {
        List<ItemPesanan> items = pesanan.getItems();
        if (items.isEmpty()) {
            System.out.println("Keranjang pesanan masih kosong.");
            return;
        }

        System.out.println("\n--- ISI KERANJANG ---");
        for (int i = 0; i < items.size(); i++) {
            ItemPesanan item = items.get(i);
            String catatan = item.getCatatan().isEmpty() ? "-" : item.getCatatan();
            String opsi = (item.getMenu() instanceof Makanan m && m.isDipanaskan()) ? "Dipanaskan" : "Biasa";
            System.out.printf("%d. %dx %-20s (%s) | Catatan: %-12s | Rp %,.0f\n",
                    i + 1, item.getJumlah(), item.getMenu().getNama(), opsi, catatan, item.hitungSubtotal());
        }
        System.out.printf("Subtotal Sementara: Rp %,.0f\n", pesanan.hitungSubtotal());

        int hapusIdx = bacaInt("Masukkan nomor item untuk dihapus (0 untuk kembali): ", 0, items.size());
        if (hapusIdx > 0) {
            pesanan.hapusItem(items.get(hapusIdx - 1));
            System.out.println("Item berhasil dihapus dari keranjang.");
        }
    }

    private static void pilihDiskon(Pesanan pesanan) {
        System.out.printf("\nSubtotal Kotor: Rp %,.0f\n", pesanan.hitungSubtotal());
        System.out.println("--- PILIH DISKON ---");
        System.out.println("1. Tanpa Diskon");
        System.out.println("2. Diskon Member (Persentase %)");
        System.out.println("3. Diskon Voucher (Nominal Rp)");

        int pilih = bacaInt("Pilih diskon [1-3]: ", 1, 3);
        switch (pilih) {
            case 1 -> pesanan.setDiskon(null);
            case 2 -> {
                while (true) {
                    double persen = bacaDouble("Masukkan persentase diskon (0-100): ", 0, 100);
                    try {
                        pesanan.setDiskon(new DiskonMember(persen));
                        break;
                    } catch (IllegalArgumentException ex) {
                        System.out.println("[!] " + ex.getMessage());
                    }
                }
            }
            case 3 -> {
                while (true) {
                    double nominal = bacaDouble("Masukkan nominal potongan voucher (Rp): ", 0, Double.MAX_VALUE);
                    try {
                        pesanan.setDiskon(new DiskonVoucher(nominal));
                        break;
                    } catch (IllegalArgumentException ex) {
                        System.out.println("[!] " + ex.getMessage());
                    }
                }
            }
        }

        double subtotal = pesanan.hitungSubtotal();
        double total = pesanan.hitungTotal();
        System.out.printf("Potongan Diskon : Rp %,.0f\n", subtotal - total);
        System.out.printf("TOTAL TAGIHAN   : Rp %,.0f\n", total);
    }

    private static Pembayaran prosesPembayaran(Pesanan pesanan) {
        double totalTagihan = pesanan.hitungTotal();

        while (true) {
            System.out.println("\n--- METODE PEMBAYARAN ---");
            System.out.println("1. Tunai (Cash)");
            System.out.println("2. QRIS");
            int metode = bacaInt("Pilih metode pembayaran [1-2]: ", 1, 2);

            if (metode == 1) {
                double uangDiterima = bacaDouble("Masukkan uang tunai diterima (Rp): ", 0, Double.MAX_VALUE);
                Tunai tunai = new Tunai(uangDiterima);
                if (tunai.prosesPembayaran(totalTagihan)) {
                    System.out.printf("Pembayaran Tunai Berhasil! Kembalian: Rp %,.0f\n", tunai.hitungKembalian());
                    return tunai;
                } else {
                    System.out.printf("[!] Uang kurang. Total tagihan adalah Rp %,.0f\n", totalTagihan);
                }
            } else {
                System.out.print("Masukkan kode QR (harus diawali 'QRIS-', contoh QRIS-001): ");
                String kodeQR = scanner.nextLine().trim();
                QRIS qris = new QRIS(kodeQR);
                if (qris.prosesPembayaran(totalTagihan)) {
                    System.out.println("Pembayaran QRIS Berhasil!");
                    return qris;
                } else {
                    System.out.println("[!] Kode QR tidak valid. Pastikan diawali 'QRIS-'.");
                }
            }
        }
    }

    private static void cetakStrukLengkap(Pesanan pesanan, Pembayaran pembayaran) {
        System.out.println();
        Struk struk = new Struk("STRK-" + pesanan.getIdPesanan());
        struk.cetak();

        System.out.println("ID Pesanan : " + pesanan.getIdPesanan());
        System.out.println("No. Meja   : " + pesanan.getNomorMeja());
        System.out.println("Status     : " + pesanan.getStatus());
        System.out.println("----------------------------");
        for (ItemPesanan item : pesanan.getItems()) {
            System.out.printf("%dx %-18s Rp %,.0f\n",
                    item.getJumlah(), item.getMenu().getNama(), item.hitungSubtotal());
            if (!item.getCatatan().isEmpty()) {
                System.out.println("   Catatan: " + item.getCatatan());
            }
        }
        double subtotal = pesanan.hitungSubtotal();
        double total = pesanan.hitungTotal();
        System.out.println("----------------------------");
        System.out.printf("Subtotal   : Rp %,.0f\n", subtotal);
        System.out.printf("Diskon     : -Rp %,.0f\n", subtotal - total);
        System.out.printf("Total Bayar: Rp %,.0f\n", total);
        if (pembayaran instanceof Tunai tunai) {
            System.out.printf("Tunai      : Rp %,.0f\n", tunai.getUangDiterima());
            System.out.printf("Kembalian  : Rp %,.0f\n", tunai.hitungKembalian());
        } else if (pembayaran instanceof QRIS qris) {
            System.out.println("QRIS Kode  : " + qris.getKodeQR());
        }
        System.out.println("============================");
    }

    private static void tampilkanLaporanPenjualan() {
        System.out.println("\n=== REKAP LAPORAN TRANSAKSI ===");
        if (riwayatPesanan.isEmpty()) {
            System.out.println("Belum ada transaksi yang selesai pada sesi ini.");
            return;
        }
        double totalOmzet = 0;
        for (Pesanan p : riwayatPesanan) {
            System.out.printf("- %s | Meja %d | %d item | Total: Rp %,.0f\n",
                    p.getIdPesanan(), p.getNomorMeja(), p.getItems().size(), p.hitungTotal());
            totalOmzet += p.hitungTotal();
        }
        System.out.println("-------------------------------");
        System.out.printf("Total Transaksi : %d\n", riwayatPesanan.size());
        System.out.printf("Total Omzet     : Rp %,.0f\n", totalOmzet);
    }

    private static int bacaInt(String prompt, int min, int max) {
        while (true) {
            System.out.print(prompt);
            String input = scanner.nextLine().trim();
            try {
                int nilai = Integer.parseInt(input);
                if (nilai >= min && nilai <= max) {
                    return nilai;
                }
                System.out.printf("[!] Masukkan angka antara %d hingga %d.\n", min, max);
            } catch (NumberFormatException ex) {
                System.out.println("[!] Input harus berupa angka bulat.");
            }
        }
    }

    private static double bacaDouble(String prompt, double min, double max) {
        while (true) {
            System.out.print(prompt);
            String input = scanner.nextLine().trim();
            try {
                double nilai = Double.parseDouble(input);
                if (!Double.isNaN(nilai) && nilai >= min && nilai <= max) {
                    return nilai;
                }
                System.out.println("[!] Nilai di luar batas yang diperbolehkan.");
            } catch (NumberFormatException ex) {
                System.out.println("[!] Input harus berupa angka.");
            }
        }
    }
}
