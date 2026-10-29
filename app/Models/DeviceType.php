<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    protected $table = 'device_types';
    protected $primaryKey = 'id_jenis';

    protected $fillable = [
        'id_kategori',
        'nama_jenis',
    ];

    public function category()
    {
        return $this->belongsTo(InfrastructureCategory::class, 'id_kategori', 'id_kategori');
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'id_jenis', 'id_jenis');
    }
}
