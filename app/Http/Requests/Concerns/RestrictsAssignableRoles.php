<?php

namespace App\Http\Requests\Concerns;

use App\Models\Role;
use Illuminate\Validation\Rule;

trait RestrictsAssignableRoles
{
    /**
     * Aturan validasi kolom id_peran.
     *
     * Pengguna non-Admin tidak boleh menetapkan role Admin maupun role yang
     * permission-nya lebih luas daripada role yang ia miliki, agar tidak
     * terjadi eskalasi hak akses melalui pengelolaan pengguna.
     *
     * @return array<int, mixed>
     */
    protected function roleAssignmentRules(): array
    {
        $rules = ['required', 'integer', Rule::exists('roles', 'id_peran')];

        $actor = $this->user();

        if ($actor === null || $actor->isAdmin()) {
            return $rules;
        }

        $adminRoleId = Role::query()
            ->where('nama_peran', 'Admin')
            ->value('id_peran');

        if ($adminRoleId !== null) {
            $rules[] = Rule::notIn([(int) $adminRoleId]);
        }

        $allowedRoleIds = $actor->assignableRoles()
            ->pluck('id_peran')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rules[] = Rule::in($allowedRoleIds);

        return $rules;
    }
}
