# InfraCheck: Sistem Monitoring dan Pelaporan Infrastruktur Teknologi Kampus

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-v3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

> **Tugas Praktikum Pengembangan Perangkat Lunak (PPL)**  
> **Kelas:** SI-48-05  
> **Kelompok:** 9 (Ekstensi InfraCheck)  
> **Fokus SDGs:** *Sustainable Development Goal 9 (Industry, Innovation, and Infrastructure)*

---

## 👥 Identitas Tim Pengembang

Pengembangan aplikasi **InfraCheck** dilakukan secara kolaboratif oleh 7 anggota tim dengan pembagian peran dan tanggung jawab *Product Backlog Item* (PBI) sebagai berikut:

| No. | Nama Mahasiswa | NIM | Peran / Fokus | Tanggung Jawab PBI |
| :---: | :--- | :---: | :--- | :--- |
| **1** | **Ihsan Farhan Moch. Kurnia** | `102022580062` | Pendahuluan & Pengguna | **PBI 1**: Mengelola data pengguna<br>**PBI 2**: Mengelola profil pengguna<br>**PBI 3**: Mengelola hak akses pengguna |
| **2** | **Ikhsan Fadli Nugraha** | `102022580019` | Deskripsi Produk | **PBI 4**: Mengelola data gedung<br>**PBI 5**: Mengelola data ruangan<br>**PBI 6**: Mengelola kategori infrastruktur teknologi |
| **3** | **Arbi Laksmana Putra Rosydin** | `102022580029` | Kebutuhan Fungsional | **PBI 7**: Mengelola jenis perangkat<br>**PBI 8**: Mengelola data perangkat teknologi<br>**PBI 9**: Menampilkan detail perangkat |
| **4** | **Gilang Restu Ananda** | `102022580055` | Proses Bisnis & Use Case | **PBI 10**: Membuat laporan kerusakan<br>**PBI 11**: Memantau laporan kerusakan<br>**PBI 12**: Memperbarui informasi laporan |
| **5** | **Andi Muh. Arif Darma Saputra M** | `102022580023` | Kebutuhan Non-Fungsional & Teknis | **PBI 13**: Memverifikasi laporan kerusakan<br>**PBI 14**: Menentukan prioritas penanganan<br>**PBI 15**: Menugaskan laporan kepada teknisi |
| **6** | **Gabriella Angelina** | `102022500100` | Rancangan Data & Class | **PBI 16**: Memperbarui progres perbaikan<br>**PBI 17**: Menjadwalkan perbaikan<br>**PBI 18**: Mengunggah dokumentasi hasil perbaikan |
| **7** | **Endar Sulistyaningsih** | `102022500379` | Arsitektur, Mockup & Sprint | **PBI 19**: Memantau status laporan<br>**PBI 20**: Memberikan tanggapan pada laporan<br>**PBI 21**: Melihat riwayat penanganan |

---

## 📌 Tentang InfraCheck

**InfraCheck** adalah sistem informasi berbasis web yang dirancang untuk memusatkan dan mengefisiensikan pemantauan, pelaporan, serta perbaikan infrastruktur teknologi di lingkungan kampus (seperti komputer lab, laptop, proyektor, printer, access point WiFi, switch, dan router).

### Transformasi Proses Bisnis (AS-IS vs TO-BE)
- **Sebelumnya (AS-IS):** Pelaporan disampaikan secara lisan atau pesan instan terpisah, pencatatan manual, sulit menelusuri lokasi aset, progres perbaikan tidak transparan, dan riwayat penanganan tercecer.
- **Dengan InfraCheck (TO-BE):** Seluruh laporan tercatat terpusat dengan foto bukti, validasi & penetapan prioritas terstruktur oleh Admin, penugasan teknisi terlacak dengan jadwal & stepper progres perbaikan (0-100%), hingga konfirmasi & rating kepuasan oleh Pelapor setelah perbaikan selesai.

---

## 🔑 Aktor & Hak Akses Sistem

Sistem mendukung 4 tingkatan hak akses berbasis peran (*Role-Based Access Control*):

| Aktor | Deskripsi & Hak Akses Utama |
| :--- | :--- |
| **Pelapor** *(Mahasiswa, Dosen, Staf)* | Membuat laporan kerusakan dengan foto bukti, memantau status laporan mandiri, memperbarui laporan (sebelum diverifikasi), dan memberikan ulasan/rating perbaikan. |
| **Admin** *(Unit IT)* | Mengelola seluruh master data (gedung, ruangan, kategori, jenis, perangkat, pengguna, role), memverifikasi laporan masuk, menentukan prioritas, dan menugaskan teknisi. |
| **Teknisi** | Menerima penugasan perbaikan, mengatur jadwal kerja, memperbarui progres perbaikan berkala, dan mengunggah foto bukti penyelesaian perbaikan. |
| **Pimpinan** | Memantau rekapitulasi dashboard, statistik kerusakan per gedung, perangkat sering rusak, dan riwayat penanganan (*read-only*). |

---

## 🏗️ Modul & Kebutuhan Fungsional

Sistem diturunkan ke dalam 5 Kebutuhan Fungsional utama (**FR-01 s/d FR-05**) dan 21 *Product Backlog Items* (**PBI**):

1. **FR-01 (Pengelolaan Pengguna & Hak Akses):** Otentikasi login ganda (Email atau NIM/NIP), pengelolaan akun pengguna, dan pengaturan izin per peran.
2. **FR-02 (Pengelolaan Master Data Infrastruktur):** Hierarki lokasi kampus (*Gedung ➔ Ruangan*) dan taksonomi aset (*Kategori ➔ Jenis Perangkat ➔ Aset Perangkat*).
3. **FR-03 (Pelaporan Kerusakan):** Formulir pengajuan laporan kerusakan, deteksi laporan ganda pada perangkat aktif, dan unggah foto dokumentasi kerusakan.
4. **FR-04 (Verifikasi & Penugasan):** Validasi kelayakan laporan oleh admin, klasifikasi urgensi vs prioritas dampak, dan penugasan ke teknisi terkait.
5. **FR-05 (Penanganan & Monitoring Progres):** Pencatatan tahapan pengerjaan teknisi, manajemen kalender jadwal, dokumentasi *before/after*, audit riwayat status (*Status History*), dan umpan balik pelapor.

---

## 🛠️ Arsitektur & Teknologi

- **Pola Arsitektur:** Client-Server *Three-Tier* berbasis **Model-View-Controller (MVC)**
- **Backend:** [Laravel](https://laravel.com) (PHP 8.4)
- **Database:** [MySQL 8.0](https://www.mysql.com/) dengan 18 entitas relasional (*Foreign Keys, Cascades, Indexes*)
- **ORM:** Laravel Eloquent ORM
- **Frontend:** Blade Templating, Tailwind CSS, JavaScript Vanilla
- **Kontainerisasi:** Docker & Docker Compose (Nginx, PHP-FPM, MySQL, phpMyAdmin)

---

## 🚀 Panduan Menjalankan Sistem (Local Setup)

Proyek ini telah dikonfigurasi penuh dengan **Docker** sehingga dapat dijalankan dengan mudah di lingkungan lokal tanpa perlu menginstal PHP/MySQL secara manual di komputer host.

### 1. Prasyarat
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) sudah terpasang dan berjalan.
- [Git](https://git-scm.com/)

### 2. Kloning Repository
```bash
git clone https://github.com/PPL-No-9-Kelompok-Ekstensi-InfraCheck/SI4805-KELH-IInfraCheck.git
cd SI4805-KELH-IInfraCheck/SI4805_KELH
```

### 3. Konfigurasi Environment
Salin file environment Docker yang telah disediakan:
```bash
cp .env.docker .env
```

### 4. Menjalankan Docker Container
Jalankan seluruh service (Nginx, PHP 8.4 FPM, MySQL 8.0, dan phpMyAdmin):
```bash
docker compose up -d --build
```

### 5. Inisialisasi Database & Seeder
Jalankan migrasi 18 tabel ERD dan data seeder bawaan:
```bash
docker compose exec app php artisan migrate --seed
```

---

## 🌐 Akses Aplikasi & Akun Pengujian

Setelah container aktif, aplikasi dapat diakses melalui browser:

| Layanan | URL | Keterangan |
| :--- | :--- | :--- |
| **Aplikasi Web InfraCheck** | `http://localhost:8000` | Halaman Login & Dashboard |
| **phpMyAdmin** | `http://localhost:8080` | Manajemen Database (*Auto-login aktif*) |

### Akun Uji Coba Default (Semua Password: `password`):
- **Admin Unit IT:**
  - Email / Identitas: `admin@infracheck.ac.id` atau `ADM001`
  - Password: `password`
- **Teknisi:**
  - Email / Identitas: `teknisi@infracheck.ac.id` atau `TEK001`
  - Password: `password`
- **Pelapor (Mahasiswa):**
  - Email / Identitas: `pelapor@infracheck.ac.id` atau `1202220001`
  - Password: `password`
- **Pimpinan:**
  - Email / Identitas: `pimpinan@infracheck.ac.id` atau `PMP001`
  - Password: `password`

---

## 🧪 Menjalankan Automated Testing

Untuk memvalidasi integritas fungsional otentikasi dan alur dashboard:
```bash
docker compose exec app php artisan test --filter=LoginTest
```

---
© 2026 **Kelompok 9 (Ekstensi InfraCheck)** — SI-48-05 Telkom University. All rights reserved.
