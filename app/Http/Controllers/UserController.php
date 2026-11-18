<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Role;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Nama role yang berhak mengelola data pengguna.
     */
    private const ADMIN_ROLE = 'Admin';

    /**
     * Menampilkan daftar pengguna.
     */
    public function index(Request $request): View
    {
        $this->ensurePermission('users.view');

        $search = trim((string) $request->query('q', ''));

        $users = User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nim_nip', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    /**
     * Menampilkan form tambah pengguna.
     */
    public function create(): View
    {
        $this->ensurePermission('users.create');

        return view('users.create', ['roles' => Auth::user()->assignableRoles()]);
    }

    /**
     * Menyimpan pengguna baru.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->ensurePermission('users.create');

        $data = $request->validated();

        User::create([
            'id_peran' => $data['id_peran'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'kata_sandi_hash' => Hash::make($data['password']),
            'nim_nip' => $data['nim_nip'] ?? null,
            'unit' => $data['unit'] ?? null,
            'kontak' => $data['kontak'] ?? null,
            'akun_aktif' => $request->has('akun_aktif')
                ? $request->boolean('akun_aktif')
                : true,
        ]);

        return redirect()
            ->route('users.index')
            ->with('status', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit pengguna.
     */
    public function edit(User $user): View
    {
        $this->ensurePermission('users.update');
        $this->ensureTargetIsManageable($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => Auth::user()->assignableRoles(),
        ]);
    }

    /**
     * Memperbarui data pengguna.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensurePermission('users.update');
        $this->ensureTargetIsManageable($user);

        $data = $request->validated();

        $currentUser = $request->user();
        $isSelf = (int) $user->id_pengguna === (int) $currentUser->id_pengguna;

        $newRoleId = (int) $data['id_peran'];
        $newActive = $request->has('akun_aktif')
            ? $request->boolean('akun_aktif')
            : (bool) $user->akun_aktif;

        // Pengguna non-Admin tidak boleh menaikkan hak akses dirinya sendiri
        // dengan berpindah ke role yang memiliki permission lebih banyak.
        if ($isSelf && ! $currentUser->isAdmin() && $this->roleExceedsCurrentPermissions($currentUser, $newRoleId)) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'id_peran' => 'Anda tidak dapat meningkatkan hak akses Anda sendiri.',
                ]);
        }

        // Admin tidak boleh menonaktifkan akunnya sendiri, meskipun masih ada Admin lain.
        if ($isSelf && $user->akun_aktif && ! $newActive) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'user' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
                ]);
        }

        // Perubahan role/status tidak boleh menghilangkan Admin aktif terakhir.
        if ($this->wouldLeaveNoActiveAdmin($user, $newRoleId, $newActive)) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'user' => 'Perubahan tidak dapat disimpan karena akan menghilangkan Admin aktif terakhir.',
                ]);
        }

        $payload = [
            'id_peran' => $data['id_peran'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'nim_nip' => $data['nim_nip'] ?? null,
            'unit' => $data['unit'] ?? null,
            'kontak' => $data['kontak'] ?? null,
        ];

        // Password hanya diubah jika field password diisi.
        if (! empty($data['password'])) {
            $payload['kata_sandi_hash'] = Hash::make($data['password']);
        }

        if ($request->has('akun_aktif')) {
            $payload['akun_aktif'] = $request->boolean('akun_aktif');
        }

        $user->update($payload);

        // Jika Admin menurunkan role dirinya sendiri, arahkan ke dashboard
        // karena halaman pengguna sudah tidak dapat diakses.
        if ($isSelf && $this->adminRoleId() !== null && $newRoleId !== $this->adminRoleId()) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Data pengguna berhasil diperbarui. Hak akses Admin Anda telah diubah.');
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna secara permanen dari database.
     *
     * Penghapusan ditolak jika pengguna masih memiliki laporan, penugasan,
     * komentar, atau riwayat perubahan status agar data historis tetap utuh.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensurePermission('users.delete');
        $this->ensureTargetIsManageable($user);

        $currentUser = $request->user();

        if ((int) $user->id_pengguna === (int) $currentUser->id_pengguna) {
            return redirect()
                ->route('users.index')
                ->withErrors(['user' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        if ($this->wouldRemoveLastActiveAdmin($user)) {
            return redirect()
                ->route('users.index')
                ->withErrors(['user' => 'Akun tidak dapat dihapus karena akan menghilangkan Admin aktif terakhir.']);
        }

        $blockers = $this->deletionBlockers($user);

        if ($blockers !== []) {
            return redirect()
                ->route('users.index')
                ->withErrors([
                    'user' => 'Pengguna tidak dapat dihapus karena masih memiliki data terkait: '
                        .implode(', ', $blockers).'.',
                ]);
        }

        try {
            $user->delete();
        } catch (QueryException) {
            // Foreign key dapat berubah di antara pemeriksaan dan penghapusan.
            // Kembalikan pesan yang ramah, bukan halaman error SQL.
            return redirect()
                ->route('users.index')
                ->withErrors([
                    'user' => 'Pengguna tidak dapat dihapus karena masih memiliki data terkait.',
                ]);
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'Pengguna berhasil dihapus secara permanen.');
    }

    /**
     * Memastikan pengguna yang sedang login memiliki hak akses tertentu.
     */
    private function ensurePermission(string $code): void
    {
        $user = Auth::user();

        abort_unless(
            $user !== null && $user->hasPermission($code),
            403,
            'Anda tidak memiliki hak akses untuk tindakan ini.'
        );
    }

    /**
     * Memastikan pengguna non-Admin tidak mengelola akun ber-role Admin.
     */
    private function ensureTargetIsManageable(User $target): void
    {
        $actor = Auth::user();

        abort_if(
            $actor !== null && ! $actor->isAdmin() && $target->isAdmin(),
            403,
            'Anda tidak memiliki hak akses untuk mengelola akun Admin.'
        );
    }

    /**
     * Mengecek apakah role tujuan memiliki permission di luar role pengguna.
     */
    private function roleExceedsCurrentPermissions(User $user, int $roleId): bool
    {
        $currentCodes = $user->permissionCodes();

        $newRole = Role::with('permissions')->find($roleId);
        $newCodes = $newRole?->permissions->pluck('kode')->all() ?? [];

        return array_diff($newCodes, $currentCodes) !== [];
    }

    /**
     * ID role Admin, atau null jika role tidak tersedia.
     */
    private function adminRoleId(): ?int
    {
        $adminRoleId = Role::where('nama_peran', self::ADMIN_ROLE)->value('id_peran');

        return $adminRoleId === null ? null : (int) $adminRoleId;
    }

    /**
     * Mengecek apakah pengguna berstatus Admin aktif.
     */
    private function isActiveAdmin(User $user): bool
    {
        $adminRoleId = $this->adminRoleId();

        return $adminRoleId !== null
            && $user->akun_aktif
            && (int) $user->id_peran === $adminRoleId;
    }

    /**
     * Jumlah Admin aktif selain pengguna yang diberikan.
     */
    private function otherActiveAdminCount(User $user): int
    {
        return User::query()
            ->where('id_peran', $this->adminRoleId())
            ->where('akun_aktif', true)
            ->where('id_pengguna', '!=', $user->id_pengguna)
            ->count();
    }

    /**
     * Mengecek apakah penonaktifan akan menghilangkan Admin aktif terakhir.
     */
    private function wouldRemoveLastActiveAdmin(User $user): bool
    {
        return $this->isActiveAdmin($user)
            && $this->otherActiveAdminCount($user) === 0;
    }

    /**
     * Daftar data terkait yang menghalangi penghapusan pengguna.
     *
     * @return list<string>
     */
    private function deletionBlockers(User $user): array
    {
        $blockers = [];

        $hasReports = Report::query()
            ->where(function ($query) use ($user) {
                $query->where('id_pelapor', $user->id_pengguna)
                    ->orWhere('id_admin_verifikator', $user->id_pengguna);
            })
            ->exists();

        if ($hasReports) {
            $blockers[] = 'laporan';
        }

        $hasAssignments = Assignment::query()
            ->where(function ($query) use ($user) {
                $query->where('id_teknisi', $user->id_pengguna)
                    ->orWhere('id_admin_penugas', $user->id_pengguna);
            })
            ->exists();

        if ($hasAssignments) {
            $blockers[] = 'penugasan';
        }

        if (Comment::query()->where('id_pengguna', $user->id_pengguna)->exists()) {
            $blockers[] = 'komentar';
        }

        if (StatusHistory::query()->where('id_pengubah', $user->id_pengguna)->exists()) {
            $blockers[] = 'riwayat perubahan status';
        }

        return $blockers;
    }

    /**
     * Mengecek apakah perubahan role/status akan menghilangkan Admin aktif terakhir.
     */
    private function wouldLeaveNoActiveAdmin(User $user, int $newRoleId, bool $newActive): bool
    {
        $adminRoleId = $this->adminRoleId();

        if ($adminRoleId === null || ! $this->isActiveAdmin($user)) {
            return false;
        }

        if ($newActive && $newRoleId === $adminRoleId) {
            return false;
        }

        return $this->otherActiveAdminCount($user) === 0;
    }
}
