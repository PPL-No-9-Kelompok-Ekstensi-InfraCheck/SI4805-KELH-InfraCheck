<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Aksi Hak Akses
    |--------------------------------------------------------------------------
    | Empat aksi standar yang ditampilkan sebagai checkbox pada halaman
    | pengelolaan hak akses.
    */
    'actions' => [
        'view' => 'Lihat',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modul dan Aksi yang Tersedia
    |--------------------------------------------------------------------------
    | Setiap modul menghasilkan permission dengan kode "{kode}.{aksi}".
    | Hanya aksi yang relevan untuk modul tersebut yang dibuat.
    */
    'groups' => [
        'Umum' => [
            ['kode' => 'dashboard', 'nama' => 'Dashboard', 'aksi' => ['view']],
        ],
        'Master Data' => [
            ['kode' => 'buildings', 'nama' => 'Gedung', 'aksi' => ['view', 'create', 'update', 'delete']],
            ['kode' => 'rooms', 'nama' => 'Ruangan', 'aksi' => ['view', 'create', 'update', 'delete']],
            ['kode' => 'categories', 'nama' => 'Kategori Infrastruktur', 'aksi' => ['view', 'create', 'update', 'delete']],
            ['kode' => 'device-types', 'nama' => 'Jenis Perangkat', 'aksi' => ['view', 'create', 'update', 'delete']],
            ['kode' => 'devices', 'nama' => 'Perangkat', 'aksi' => ['view', 'create', 'update', 'delete']],
        ],
        'Laporan' => [
            ['kode' => 'reports', 'nama' => 'Laporan', 'aksi' => ['view', 'create', 'update', 'delete']],
            ['kode' => 'report-verification', 'nama' => 'Verifikasi Laporan', 'aksi' => ['view', 'update']],
            ['kode' => 'report-priorities', 'nama' => 'Prioritas Penanganan', 'aksi' => ['view', 'update']],
            ['kode' => 'assignments', 'nama' => 'Penugasan Teknisi', 'aksi' => ['view', 'create', 'update', 'delete']],
            ['kode' => 'schedules', 'nama' => 'Jadwal Perbaikan', 'aksi' => ['view', 'create', 'update', 'delete']],
        ],
        'Pemantauan' => [
            ['kode' => 'report-statuses', 'nama' => 'Status Laporan', 'aksi' => ['view']],
            ['kode' => 'report-histories', 'nama' => 'Riwayat Penanganan', 'aksi' => ['view']],
        ],
        'Pengaturan' => [
            ['kode' => 'users', 'nama' => 'Pengguna', 'aksi' => ['view', 'create', 'update', 'delete']],
            ['kode' => 'access', 'nama' => 'Hak Akses', 'aksi' => ['view', 'update']],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hak Akses Default per Role
    |--------------------------------------------------------------------------
    | Hanya diterapkan saat role belum memiliki hak akses sama sekali agar
    | pengaturan yang sudah diubah Admin tidak tertimpa.
    */
    'defaults' => [
        'Admin' => '*',
        'Pelapor' => [
            'dashboard.view',
            'reports.view',
            'reports.create',
            'report-statuses.view',
            'report-histories.view',
        ],
        'Teknisi' => [
            'dashboard.view',
            'reports.view',
            'reports.update',
            'report-statuses.view',
            'report-histories.view',
        ],
        'Pimpinan' => [
            'dashboard.view',
            'reports.view',
            'report-verification.view',
            'report-verification.update',
            'report-priorities.view',
            'report-priorities.update',
            'report-statuses.view',
            'report-histories.view',
        ],
    ],
];
