<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id_pengguna';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_peran',
        'nama',
        'email',
        'kata_sandi_hash',
        'nim_nip',
        'unit',
        'kontak',
        'akun_aktif',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'kata_sandi_hash',
        'remember_token',
    ];

    /**
     * Get the name of the password attribute for the user.
     */
    public function getAuthPasswordName(): string
    {
        return 'kata_sandi_hash';
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string
    {
        return $this->kata_sandi_hash;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akun_aktif' => 'boolean',
            'kata_sandi_hash' => 'hashed',
        ];
    }

    /**
     * Relasi ke tabel roles.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_peran', 'id_peran');
    }

    /**
     * Relasi ke penugasan sebagai teknisi.
     */
    public function technicianAssignments()
    {
        return $this->hasMany(Assignment::class, 'id_teknisi', 'id_pengguna');
    }

    /**
     * Relasi ke penugasan yang sedang aktif.
     */
    public function activeAssignments()
    {
        return $this->hasMany(Assignment::class, 'id_teknisi', 'id_pengguna')->where('status_penugasan', 'aktif');
    }

    /**
     * Accessor keahlian teknisi (dari kolom unit atau default).
     */
    public function getKeahlianAttribute(): string
    {
        return $this->unit ?: 'Infrastruktur Umum';
    }

    /**
     * Mengecek apakah pengguna memiliki role dengan nama tertentu.
     *
     * @param  string|list<string>  $name
     */
    public function hasRole(string|array $name): bool
    {
        if ($this->role === null) {
            return false;
        }

        foreach ((array) $name as $roleName) {
            if (strcasecmp($this->role->nama_peran, $roleName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mengecek apakah pengguna memegang role Admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    /**
     * Daftar kode hak akses yang dimiliki role pengguna.
     *
     * @return list<string>
     */
    public function permissionCodes(): array
    {
        return $this->role?->permissions->pluck('kode')->all() ?? [];
    }

    /**
     * Role yang boleh ditetapkan oleh pengguna ini.
     *
     * Admin boleh menetapkan role apa pun. Pengguna non-Admin hanya boleh
     * menetapkan role yang permission-nya merupakan subset dari role-nya
     * sendiri, sehingga tidak terjadi eskalasi hak akses.
     *
     * @return Collection<int, Role>
     */
    public function assignableRoles(): Collection
    {
        $roles = Role::with('permissions')->orderBy('nama_peran')->get();

        if ($this->isAdmin()) {
            return $roles;
        }

        $ownCodes = $this->permissionCodes();

        return $roles
            ->reject(fn (Role $role) => strcasecmp($role->nama_peran, 'Admin') === 0)
            ->filter(fn (Role $role) => array_diff($role->permissions->pluck('kode')->all(), $ownCodes) === [])
            ->values();
    }

    /**
     * Mengecek apakah pengguna aktif memiliki hak akses tertentu.
     */
    public function hasPermission(string $code): bool
    {
        if (! $this->akun_aktif || $this->role === null) {
            return false;
        }

        return $this->role->permissions->contains('kode', $code);
    }

    /**
     * Mengecek apakah pengguna aktif memiliki salah satu dari hak akses yang diberikan.
     *
     * @param  iterable<int, string>  $codes
     */
    public function hasAnyPermission(iterable $codes): bool
    {
        foreach ($codes as $code) {
            if ($this->hasPermission($code)) {
                return true;
            }
        }

        return false;
    }
}
