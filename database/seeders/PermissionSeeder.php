<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
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

            // Jangan menimpa pengaturan hak akses yang sudah diubah Admin.
            if ($role === null || $role->permissions()->exists()) {
                continue;
            }

            if ($codes === '*') {
                $codes = array_keys($permissionIds);
            }

            $role->permissions()->sync(
                array_values(array_map(fn (string $code) => $permissionIds[$code], $codes))
            );
        }
    }
}
