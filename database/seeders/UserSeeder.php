<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed akun pengguna default (Tabel 2). Bergantung pada RoleSeeder.
     */
    public function run(): void
    {
        $pengguna = [
            ['admin@infracheck.ac.id', 'Admin', 'Administrator Sistem', 'ADM001', 'Biro Sistem Informasi', '081234567890'],
            ['teknisi@infracheck.ac.id', 'Teknisi', 'Budi Santoso (Teknisi)', 'TEK001', 'Divisi Infrastruktur & Jaringan', '081298765432'],
            ['pelapor@infracheck.ac.id', 'Pelapor', 'Andi Saputra (Mahasiswa)', '1202220001', 'Sistem Informasi', '081345678901'],
            ['pimpinan@infracheck.ac.id', 'Pimpinan', 'Prof. Dr. Ir. Pimpinan', 'PMP001', 'Direktorat Sarana Prasarana', '081122334455'],
        ];

        foreach ($pengguna as [$email, $namaPeran, $nama, $nimNip, $unit, $kontak]) {
            $peran = Role::where('nama_peran', $namaPeran)->firstOrFail();

            User::firstOrCreate(
                ['email' => $email],
                [
                    'id_peran'         => $peran->id_peran,
                    'nama'             => $nama,
                    'kata_sandi_hash'  => Hash::make('password'),
                    'nim_nip'          => $nimNip,
                    'unit'             => $unit,
                    'kontak'           => $kontak,
                    'akun_aktif'       => true,
                ]
            );
        }
    }
}
