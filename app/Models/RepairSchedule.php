<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairSchedule extends Model
{
    protected $table = 'repair_schedules';
    protected $primaryKey = 'id_jadwal';

    protected $fillable = [
        'id_penugasan',
        'waktu_mulai',
        'target_selesai',
        'catatan_jadwal',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'target_selesai' => 'datetime',
        ];
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'id_penugasan', 'id_penugasan');
    }
}
