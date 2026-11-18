<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $primaryKey = 'id_hak_akses';

    protected $fillable = [
        'kode',
        'nama',
        'grup',
    ];

    /**
     * Relasi ke tabel roles melalui tabel pivot permission_role.
     */
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'permission_role',
            'id_hak_akses',
            'id_peran'
        )->withTimestamps();
    }
}
