<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccessController extends Controller
{
    /**
     * Nama role yang berhak mengelola matriks hak akses.
     */
    private const ADMIN_ROLE = 'Admin';

    /**
     * Hak akses yang wajib dimiliki seluruh role.
     */
    private const ALWAYS_GRANTED = ['dashboard.view'];

    /**
     * Hak akses modul Hak Akses yang hanya boleh dimiliki role Admin.
     */
    private const ADMIN_ONLY = ['access.view', 'access.update'];

    /**
     * Menampilkan matriks hak akses per role.
     */
    public function index(): View
    {
        $this->ensureAdmin();

        $roles = Role::with('permissions')->orderBy('id_peran')->get();
        $permissions = Permission::orderBy('id_hak_akses')->get()->keyBy('kode');

        $selected = $roles->mapWithKeys(
            fn (Role $role) => [
                (int) $role->id_peran => $role->permissions
                    ->pluck('id_hak_akses')
                    ->map(fn ($id) => (int) $id)
                    ->all(),
            ]
        );

        [$lockedCells, $blockedCells] = $this->cellRules($roles);

        return view('access.index', compact('roles', 'permissions', 'selected', 'lockedCells', 'blockedCells'));
    }

    /**
     * Menyimpan perubahan hak akses seluruh role.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['integer', 'exists:permissions,id_hak_akses'],
        ], [
            'permissions.required' => 'Data hak akses tidak dikirim sehingga tidak ada perubahan yang disimpan.',
            'permissions.*.*.exists' => 'Hak akses yang dipilih tidak tersedia.',
            'permissions.*.*.integer' => 'Hak akses yang dipilih tidak valid.',
        ]);

        $roles = Role::orderBy('id_peran')->get();
        $submitted = [];

        foreach ($roles as $role) {
            $roleId = (int) $role->id_peran;
            $ids = (array) $request->input('permissions.'.$roleId, []);

            $submitted[$roleId] = array_values(array_unique(array_map('intval', $ids)));
        }

        $this->guardMandatoryPermissions($roles, $submitted);

        DB::transaction(function () use ($roles, $submitted): void {
            foreach ($roles as $role) {
                $role->permissions()->sync($submitted[(int) $role->id_peran]);
            }
        });

        return redirect()
            ->route('access.index')
            ->with('status', 'Hak akses berhasil diperbarui.');
    }

    /**
     * Aturan sel matriks: permission yang selalu terkunci dan yang tidak boleh
     * diberikan kepada role tertentu.
     *
     * @param  Collection<int, Role>  $roles
     * @return array{0: array<int, list<string>>, 1: array<int, list<string>>}
     */
    private function cellRules($roles): array
    {
        $adminRoleId = Role::where('nama_peran', self::ADMIN_ROLE)->value('id_peran');
        $adminRoleId = $adminRoleId === null ? null : (int) $adminRoleId;

        $lockedCells = [];
        $blockedCells = [];

        foreach ($roles as $role) {
            $roleId = (int) $role->id_peran;
            $lockedCells[$roleId] = self::ALWAYS_GRANTED;

            if ($adminRoleId !== null && $roleId === $adminRoleId) {
                $lockedCells[$roleId] = array_merge($lockedCells[$roleId], self::ADMIN_ONLY);
            } else {
                $blockedCells[$roleId] = self::ADMIN_ONLY;
            }
        }

        return [$lockedCells, $blockedCells];
    }

    /**
     * Memastikan permission wajib tetap ada dan permission khusus Admin tidak
     * diberikan kepada role lain, termasuk lewat request manual.
     *
     * @param  Collection<int, Role>  $roles
     * @param  array<int, list<int>>  $submitted
     *
     * @throws ValidationException
     */
    private function guardMandatoryPermissions($roles, array $submitted): void
    {
        $mandatoryIds = $this->permissionIds(self::ALWAYS_GRANTED);
        $adminOnlyIds = $this->permissionIds(self::ADMIN_ONLY);

        $adminRoleId = Role::where('nama_peran', self::ADMIN_ROLE)->value('id_peran');
        $adminRoleId = $adminRoleId === null ? null : (int) $adminRoleId;

        foreach ($roles as $role) {
            $roleId = (int) $role->id_peran;
            $ids = $submitted[$roleId] ?? [];

            foreach ($mandatoryIds as $permissionId) {
                if (! in_array($permissionId, $ids, true)) {
                    throw ValidationException::withMessages([
                        'permissions' => 'Hak akses "Lihat Dashboard" wajib dimiliki setiap role dan tidak dapat dicabut.',
                    ]);
                }
            }

            if ($adminRoleId !== null && $roleId === $adminRoleId) {
                foreach ($adminOnlyIds as $permissionId) {
                    if (! in_array($permissionId, $ids, true)) {
                        throw ValidationException::withMessages([
                            'permissions' => 'Hak akses "Lihat" dan "Ubah" pada modul Hak Akses untuk role Admin tidak dapat dicabut.',
                        ]);
                    }
                }

                continue;
            }

            foreach ($adminOnlyIds as $permissionId) {
                if (in_array($permissionId, $ids, true)) {
                    throw ValidationException::withMessages([
                        'permissions' => 'Hak akses modul Hak Akses hanya dapat dimiliki role Admin.',
                    ]);
                }
            }
        }
    }

    /**
     * ID permission berdasarkan kode.
     *
     * @param  list<string>  $codes
     * @return list<int>
     */
    private function permissionIds(array $codes): array
    {
        return Permission::query()
            ->whereIn('kode', $codes)
            ->pluck('id_hak_akses')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Memastikan hanya role Admin yang dapat mengelola matriks hak akses.
     */
    private function ensureAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user !== null && $user->isAdmin(),
            403,
            'Hanya Admin yang dapat mengelola hak akses.'
        );
    }
}
