<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Modul yang hak akses default-nya juga diberikan ke role yang sudah punya
     * hak akses (penambahan saja, tidak mencabut yang sudah ada).
     *
     * @var list<string>
     */
    private const MODUL_BARU = ['repair-documents'];

    /**
     * Mendaftarkan daftar hak akses dan menetapkan default per role.
     */
    public function run(): void
    {
        $actions = config('permissions.actions');
        $permissionIds = [];

        foreach (config('permissions.groups') as $groupName => $modules) {
            foreach ($modules as $module) {
                foreach ($module['aksi'] as $action) {
                    $code = $module['kode'].'.'.$action;

                    $model = Permission::updateOrCreate(
                        ['kode' => $code],
                        [
                            'nama' => $actions[$action].' '.$module['nama'],
                            'grup' => $groupName,
                        ]
                    );

                    $permissionIds[$code] = $model->id_hak_akses;
                }
            }
        }

        foreach (config('permissions.defaults') as $roleName => $codes) {
            $role = Role::where('nama_peran', $roleName)->first();

            if ($role === null) {
                continue;
            }

            if ($codes === '*') {
                $codes = array_keys($permissionIds);
            }

            // Jangan menimpa pengaturan hak akses yang sudah diubah Admin.
            if ($role->permissions()->exists()) {
                // Role sudah punya hak akses: hanya tambahkan default modul baru.
                $codeModulBaru = array_filter(
                    $codes,
                    fn (string $code): bool => in_array(explode('.', $code)[0], self::MODUL_BARU, true)
                );

                $role->permissions()->syncWithoutDetaching(
                    array_values(array_map(fn (string $code) => $permissionIds[$code], $codeModulBaru))
                );

                continue;
            }

            $role->permissions()->sync(
                array_values(array_map(fn (string $code) => $permissionIds[$code], $codes))
            );
        }
    }
}
