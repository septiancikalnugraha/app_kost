# Aplikasi Manajemen Kost

Aplikasi web berbasis PHP untuk mengelola kost secara modern dan efisien. Mendukung manajemen kamar, penghuni, tagihan, barang kost, barang bawaan, laporan, serta fitur export PDF dan print yang profesional.

## Fitur Utama
- **Dashboard Admin**: Statistik kamar, penghuni, pendapatan, barang kost, grafik pendapatan bulanan.
- **Manajemen Penghuni**: Tambah, edit, hapus, dan cetak data penghuni.
- **Manajemen Kamar**: Kelola kamar, status, harga, dan penghuni.
- **Manajemen Tagihan**: Buat, edit, hapus, dan cetak tagihan bulanan.
- **Manajemen Barang Kost & Barang Bawaan**: Kelola inventaris kost dan barang bawaan penghuni.
- **Laporan**: Rekap data kamar, penghuni, tagihan, barang, dan export PDF.
- **Export PDF**: Cetak seluruh rekap laporan ke PDF dengan tampilan rapi.
- **Print Tabel**: Print tabel data langsung dari halaman, hanya tabel dan judul yang tercetak.
- **Login Admin**: Sistem autentikasi dan proteksi halaman.

## Instalasi
1. **Clone repository** ke folder web server Anda (misal `htdocs` untuk XAMPP):
   ```
   git clone <repo-url> app_kost
   ```
2. **Import database**
   - Import file `app_kost.sql` ke MySQL Anda (bisa lewat phpMyAdmin).
3. **Install Composer dependencies**
   - Pastikan [Composer](https://getcomposer.org/) sudah terinstall.
   - Jalankan di folder project:
     ```
     composer install
     composer require dompdf/dompdf
     ```
4. **Konfigurasi koneksi database**
   - Edit file `config/database.php` jika perlu menyesuaikan host, user, password, atau nama database.
5. **Jalankan aplikasi**
   - Buka browser ke `http://localhost/app_kost/login.php`
   - Login dengan user admin default (lihat di file SQL, biasanya username: `admin`, password: `admin123`)

## Struktur Folder
```
app_kost/
├── config/
│   └── database.php
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── manajemen_penghuni.php
├── manajemen_kamar.php
├── manajemen_tagihan.php
├── manajemen_barang.php
├── manajemen_barang_bawaan.php
├── laporan.php
├── laporan_pdf.php
├── app_kost.sql
└── vendor/ (setelah composer install)
```

## Catatan
- Untuk fitur export PDF, pastikan folder `vendor/` dan file `vendor/autoload.php` sudah ada (hasil composer install).
- Semua halaman admin sudah responsif dan siap print/export.
- Jika ingin menambah fitur, cukup tambahkan file baru dan sesuaikan sidebar.

## Lisensi
Aplikasi ini dikembangkan untuk kebutuhan manajemen kost. Silakan gunakan, modifikasi, dan kembangkan sesuai kebutuhan Anda. 