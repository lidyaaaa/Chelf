# 📚 BookStore — Aplikasi Toko Buku Online

Aplikasi web manajemen toko buku berbasis **Laravel 12** & **Bootstrap 5** dengan pemisahan hak akses antara **Pelanggan (User)** dan **Admin Toko**. Dilengkapi dengan manajemen katalog buku & kategori, keranjang belanja, buku alamat pengiriman, transaksi real-time (dengan database row locking), cetak struk & kartu member, laporan penjualan (Web & Export CSV), serta fitur Live Chat antara pelanggan dan admin.

---

## ✨ Fitur Utama

### 👤 Pelanggan (User)
- **Katalog & Detail Buku**: Pencarian buku berdasarkan judul, penulis, ISBN, dan filter kategori.
- **Keranjang Belanja & Checkout**: Manajemen item keranjang dan buku alamat pengiriman.
- **Transaksi & Struk Pembelian**: Pembelian langsung maupun lewat keranjang, pembatalan pesanan berstatus *menunggu*, dan cetak struk transaksi.
- **Kartu Member Digital**: Cetak kartu anggota toko buku.
- **Live Chat**: Konsultasi dan tanya jawab langsung dengan Admin Toko.

### 🛠️ Admin Toko (Admin)
- **Dashboard Analitik**: Ringkasan statistik penjualan, stok buku, pesanan menunggu konfirmasi, dan total pelanggan.
- **CRUD Katalog Buku & Kategori**: Tambah, ubah, hapus buku (lengkap dengan upload sampul) serta manajemen kategori buku.
- **Manajemen Transaksi & Kasir**: Input transaksi kasir langsung, konfirmasi/setujui pesanan pelanggan (satuan maupun sekaligus per pelanggan).
- **Manajemen Pelanggan**: Daftar anggota terdaftar beserta riwayat pesanan dan kartu member.
- **Laporan Penjualan**: Filter laporan transaksi dan unduh laporan dalam format **CSV**.
- **Live Chat Admin**: Balas pesan dari seluruh pelanggan secara real-time.

---

## 📸 Screenshots Aplikasi

### 🔐 Autentikasi
| Halaman Login | Halaman Register |
| :---: | :---: |
| ![Login](documentation/login.png) | ![Register](documentation/register.png) |

---

### 👤 Tampilan Pelanggan (User)

| Dashboard Pelanggan | Katalog Buku |
| :---: | :---: |
| ![Dashboard User](documentation/user/dashboard.png) | ![Katalog User](documentation/user/katalog.png) |

| Card Buku | Detail Buku |
| :---: | :---: |
| ![Card Buku](documentation/user/card.png) | ![Detail Buku](documentation/user/detail.png) |

| Keranjang Belanja (Cart) | Riwayat Transaksi |
| :---: | :---: |
| ![Cart](documentation/user/cart.png) | ![Transaksi User](documentation/user/transaksi.png) |

| Live Chat Pelanggan | Tentang Kami (About) |
| :---: | :---: |
| ![Chat User](documentation/user/chat.png) | ![About](documentation/user/about.png) |

---

### 🛠️ Tampilan Admin

| Dashboard Admin | Kelola Katalog Buku |
| :---: | :---: |
| ![Dashboard Admin](documentation/admin/dashboard.png) | ![Katalog Admin](documentation/admin/katalog.png) |

| Kelola Transaksi | Laporan Penjualan |
| :---: | :---: |
| ![Transaksi Admin](documentation/admin/transaksi.png) | ![Laporan Admin](documentation/admin/laporan.png) |

| Daftar Pengguna | Live Chat Admin |
| :---: | :---: |
| ![Daftar Pengguna](documentation/admin/daftarpengguna.png) | ![Chat Admin](documentation/admin/chat.png) |

#### CRUD Buku & Kategori
| Tambah Buku | Edit Buku | Tambah Kategori |
| :---: | :---: | :---: |
| ![Tambah Buku](documentation/admin/crud/tambahbuku.png) | ![Edit Buku](documentation/admin/crud/editbuku.png) | ![Tambah Kategori](documentation/admin/crud/tambahkategori.png) |

---

## 🚀 Cara Instalasi & Menjalankan Project

1. **Clone repository & masuk ke direktori project**
   ```bash
   git clone <url-repository-kamu>
   cd bookstore
   ```

2. **Install dependensi PHP & JavaScript**
   ```bash
   composer install
   npm install
   ```

3. **Salin file `.env` dan generate application key**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi database di `.env` lalu jalankan migrasi & seeder**
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan server aplikasi**
   ```bash
   php artisan serve
   ```

---

## 🔑 Akun Default (Seeder)

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin Toko** | `admin@gmail.com` | `Admin12345` |
| **Pelanggan (User)** | `siswa@gmail.com` | `Siswa12345` |
