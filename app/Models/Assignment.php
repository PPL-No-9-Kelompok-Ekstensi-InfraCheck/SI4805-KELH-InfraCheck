<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'assignments';

    protected $primaryKey = 'id_penugasan';

    protected $fillable = [
        'id_laporan',
        'id_teknisi',
        'id_admin_penugas',
        'waktu_penugasan',
        'status_penugasan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_penugasan' => 'datetime',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class, 'id_laporan', 'id_laporan');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'id_teknisi', 'id_pengguna');
    }

    public function assigningAdmin()
    {
        return $this->belongsTo(User::class, 'id_admin_penugas', 'id_pengguna');
    }

    public function schedule()
    {
        return $this->hasOne(RepairSchedule::class, 'id_penugasan', 'id_penugasan');
    }

    public function progressUpdates()
    {
        return $this->hasMany(RepairProgress::class, 'id_penugasan', 'id_penugasan')->orderBy('waktu_progres', 'desc');
    }
}
