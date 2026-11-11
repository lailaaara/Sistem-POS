package com.resto;

import com.resto.model.Diskon;
import com.resto.model.DiskonMember;
import com.resto.model.DiskonVoucher;
import com.resto.model.ItemPesanan;
import com.resto.model.Makanan;
import com.resto.model.Menu;
import com.resto.model.Pembayaran;
import com.resto.model.Pesanan;
import com.resto.model.QRIS;
import com.resto.model.Tunai;

/**
 * Titik masuk utama (Entry Point) untuk pengujian alur logika kelas model restoran.
 */
public class Main {
    public static void main(String[] args) {
        System.out.println("====================================================");
        System.out.println("   SIMULASI SISTEM PEMESANAN RESTORAN (TEST RUN)   ");
        System.out.println("====================================================\n");

        // 1. Instansiasi objek Makanan (Polimorfisme: referensi Menu, objek Makanan)
        Menu menu1 = new Makanan("M01", "Nasi Goreng Spesial", 25000.0, 10, false);
        Menu menu2 = new Makanan("M02", "Ayam Bakar Madu", 30000.0, 5, true);

        // 2. Menampilkan rincian katalog menu
        System.out.println("--- DAFTAR MENU AWAL ---");
        tampilkanDetailMenu(menu1);
        tampilkanDetailMenu(menu2);

        // 3. Simulasi pemesanan & pengurangan stok
        System.out.println("\n--- SIMULASI PEMESANAN ---");
        int jumlahPesan = 3;
        System.out.println("Pelanggan memesan " + jumlahPesan + " porsi " + menu1.getNama() + "...");

        boolean berhasil = menu1.kurangiStok(jumlahPesan);
        if (berhasil) {
            double subtotal = jumlahPesan * menu1.hitungHarga();
            System.out.printf("Pesanan berhasil! Subtotal: Rp %,.2f\n", subtotal);
            System.out.println("Sisa stok " + menu1.getNama() + ": " + menu1.getStok());
        } else {
            System.out.println("Pesanan gagal! Stok tidak mencukupi.");
        }

        // 4. Simulasi kustomisasi (mengaktifkan pemanasan pada menu1)
        System.out.println("\n--- SIMULASI KUSTOMISASI LAYANAN ---");
        System.out.println("Mengubah preferensi " + menu1.getNama() + " menjadi dipanaskan (Takeaway)...");
        ((Makanan) menu1).setDipanaskan(true);
        tampilkanDetailMenu(menu1);

        // 5. Simulasi ItemPesanan (relasi aggregation ke Menu)
        System.out.println("\n--- SIMULASI ITEM PESANAN ---");
        ItemPesanan item1 = new ItemPesanan(menu1, jumlahPesan, "Pedas sedang");
        ItemPesanan item2 = new ItemPesanan(menu2, 1, "Bumbu pisah");
        tampilkanDetailItem(item1);
        tampilkanDetailItem(item2);

        double totalKotor = item1.hitungSubtotal() + item2.hitungSubtotal();
        System.out.printf("Total Kotor Pesanan: Rp %,.2f\n", totalKotor);

        // 6. Simulasi Strategy Pattern Diskon (DiskonMember & DiskonVoucher)
        System.out.println("\n--- SIMULASI STRATEGI DISKON ---");
        Diskon diskonMember = new DiskonMember(10.0);
        Diskon diskonVoucher = new DiskonVoucher(15000.0);
        Diskon voucherMelebihiTotal = new DiskonVoucher(200000.0);

        tampilkanSimulasiDiskon("Member (10%)", diskonMember, totalKotor);
        tampilkanSimulasiDiskon("Voucher (Rp 15.000)", diskonVoucher, totalKotor);
        tampilkanSimulasiDiskon("Voucher (Rp 200.000)", voucherMelebihiTotal, totalKotor);

        // 7. Simulasi Strategy Pattern Pembayaran (Tunai & QRIS)
        System.out.println("\n--- SIMULASI PEMBAYARAN ---");
        double totalTagihan = totalKotor - diskonMember.hitungDiskon(totalKotor);
        System.out.printf("Total Tagihan (setelah Diskon Member 10%%): Rp %,.2f\n", totalTagihan);

        Pembayaran bayarTunaiKurang = new Tunai(50000.0);
        tampilkanSimulasiPembayaran("Tunai (Rp 50.000)", bayarTunaiKurang, totalTagihan);

        Pembayaran bayarTunaiCukup = new Tunai(150000.0);
        tampilkanSimulasiPembayaran("Tunai (Rp 150.000)", bayarTunaiCukup, totalTagihan);

        Pembayaran bayarQRIS = new QRIS("QRIS-RESTO-20261111-001");
        tampilkanSimulasiPembayaran("QRIS Valid", bayarQRIS, totalTagihan);

        // 8. Simulasi Integrasi Kelas Pesanan
        System.out.println("\n--- SIMULASI OBJEK PESANAN TERINTEGRASI ---");
        Pesanan pesanan = new Pesanan("ORD-001", 5);
        pesanan.tambahItem(item1);
        pesanan.tambahItem(item2);
        pesanan.setDiskon(diskonVoucher);
        System.out.println("Status awal : " + pesanan.getStatus());
        System.out.printf("Subtotal    : Rp %,.2f\n", pesanan.hitungSubtotal());
        System.out.printf("Total Akhir : Rp %,.2f\n", pesanan.hitungTotal());

        Pembayaran pembayaranPesanan = new Tunai(100000.0);
        if (pembayaranPesanan.prosesPembayaran(pesanan.hitungTotal())) {
            pesanan.setPembayaran(pembayaranPesanan);
            pesanan.setStatus(Pesanan.SELESAI);
        }
        System.out.println("Ringkasan   : " + pesanan);

        System.out.println("\n====================================================");
        System.out.println("             PENGUJIAN KELAS SELESAI                ");
        System.out.println("====================================================");
    }

    private static void tampilkanDetailMenu(Menu menu) {
        System.out.printf("[%s] %-20s | Harga Dasar: Rp %,.2f | Harga Akhir: Rp %,.2f | Stok: %d\n",
                menu.getIdMenu(),
                menu.getNama(),
                menu.getHarga(),
                menu.hitungHarga(),
                menu.getStok()
        );
    }

    private static void tampilkanDetailItem(ItemPesanan item) {
        String catatan = item.getCatatan().isEmpty() ? "-" : item.getCatatan();
        System.out.printf("%dx %-20s | Catatan: %-14s | Subtotal: Rp %,.2f\n",
                item.getJumlah(),
                item.getMenu().getNama(),
                catatan,
                item.hitungSubtotal()
        );
    }

    private static void tampilkanSimulasiDiskon(String label, Diskon diskon, double totalKotor) {
        double potongan = diskon.hitungDiskon(totalKotor);
        double totalAkhir = totalKotor - potongan;
        System.out.printf("%-22s | Potongan: Rp %,.2f | Total Akhir: Rp %,.2f\n",
                label,
                potongan,
                totalAkhir
        );
    }

    private static void tampilkanSimulasiPembayaran(String label, Pembayaran pembayaran, double totalTagihan) {
        boolean sukses = pembayaran.prosesPembayaran(totalTagihan);
        if (sukses) {
            if (pembayaran instanceof Tunai tunai) {
                System.out.printf("%-20s | Status: BERHASIL | Dibayar: Rp %,.2f | Kembalian: Rp %,.2f\n",
                        label, tunai.getJumlahBayar(), tunai.hitungKembalian());
            } else {
                System.out.printf("%-20s | Status: BERHASIL | Dibayar: Rp %,.2f\n",
                        label, pembayaran.getJumlahBayar());
            }
        } else {
            System.out.printf("%-20s | Status: GAGAL\n", label);
        }
    }
}
