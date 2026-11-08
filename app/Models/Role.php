<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $primaryKey = 'id_peran';

    protected $fillable = [
        'nama_peran',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'id_peran', 'id_peran');
    }

    /**
     * Relasi ke tabel permissions melalui tabel pivot permission_role.
     */
    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_role',
            'id_peran',
            'id_hak_akses'
        )->withTimestamps();
    }
}
