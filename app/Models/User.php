<?php

namespace App\Models;

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
}
