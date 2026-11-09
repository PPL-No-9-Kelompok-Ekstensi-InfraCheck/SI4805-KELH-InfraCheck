# Konvensi Proyek InfraCheck

Sistem monitoring & pelaporan infrastruktur TI kampus. Laravel + Docker.
Baca bagian ini sebelum mengubah kode.

## Stack
- PHP 8.3+ (container memakai 8.4), Laravel 13.x, MySQL 8.0
- Frontend: Blade + Tailwind CSS + JS vanilla (tanpa framework)
- Semua perintah artisan dijalankan di dalam container:
  `docker compose exec app php artisan <perintah>`
  Jangan `php artisan` di host (vendor/ tidak ada di host).

## Struktur
- Controller: `app/Http/Controllers` (AuthController, DashboardController, dst.)
- Model: `app/Models` (18 model, satu per tabel bisnis)
- View: `resources/views` (Blade)
- Route: `routes/web.php`

## Database — WAJIB DIPERHATIKAN
- Nama tabel dan kolom memakai **Bahasa Indonesia**: `id_gedung`, `nama_peran`,
  `kode_aset`, `kata_sandi_hash`, `akun_aktif`.
- **Primary key bukan `id`.** Setiap model wajib mendefinisikan:

  ```php
  protected $table = 'buildings';
  protected $primaryKey = 'id_gedung';
  ```

- Foreign key eksplisit di setiap relasi:
  `$this->hasMany(User::class, 'id_peran', 'id_peran')`
- Kolom `timestamps()` dipakai di semua tabel bisnis.
- Password disimpan di `kata_sandi_hash`, **bukan** `password`.

## Migrasi
- Jangan pernah mengubah migration yang sudah ada di `main`. Buat migration baru.
- Format nama: `YYYY_MM_DD_NNNNNN_create_<tabel>_table.php`
- Nama tabel bisnis (18): roles, users, buildings, rooms,
  infrastructure_categories, device_types, devices, report_statuses,
  urgency_levels, priorities, reports, report_photos, assignments,
  repair_schedules, repair_progress, repair_documents, comments,
  status_histories
- Tabel infrastruktur Laravel (jangan diubah): sessions, cache, jobs

## Seeder
- Satu file per entitas (`UserSeeder`, `BuildingSeeder`, ...), dipanggil dari
  `DatabaseSeeder`. Jangan menumpuk semua data di `DatabaseSeeder::run()`
  (menyebabkan konflik merge antar-PBI).

## Gaya kode
- Komentar, pesan validasi, dan teks UI dalam **Bahasa Indonesia**.
- Ikuti gaya yang sudah ada: docblock ringkas, model tanpa `guarded`
  (pakai `$fillable`).
- Jalankan `docker compose exec app php artisan test` sebelum commit.
  Test memakai SQLite `:memory:` (lihat `phpunit.xml`), tidak menyentuh MySQL.

## Git
- Branch per anggota: `<Nama>_Branch`
- Format commit: `<PBI n>: <ringkasan>` — contoh `PBI 7: kelola jenis perangkat`
- Jangan commit: `.env`, `vendor/`, `node_modules`, `public/build`

## LARANGAN
- JANGAN jalankan `migrate:fresh`, `migrate:refresh`, atau `db:wipe`
  tanpa konfirmasi eksplisit — data seeder hilang.
- JANGAN menambah dependency Composer tanpa persetujuan (mengubah
  `composer.lock` dan memicu konflik merge untuk seluruh tim).
- JANGAN mengubah `docker-compose.yml` tanpa diskusi tim.
- JANGAN membuat migration untuk tabel milik PBI orang lain.

## Akun uji (password semua: `password`)
admin@infracheck.ac.id (Admin) · teknisi@infracheck.ac.id (Teknisi)
pelapor@infracheck.ac.id (Pelapor) · pimpinan@infracheck.ac.id (Pimpinan)
