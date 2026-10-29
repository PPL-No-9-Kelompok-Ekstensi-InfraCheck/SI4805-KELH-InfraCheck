<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfrastructureCategory extends Model
{
    protected $table = 'infrastructure_categories';
    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'nama_kategori',
    ];

    public function deviceTypes()
    {
        return $this->hasMany(DeviceType::class, 'id_kategori', 'id_kategori');
    }
}
