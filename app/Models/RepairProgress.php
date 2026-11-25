<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairProgress extends Model
{
    protected $table = 'repair_progress';
    protected $primaryKey = 'id_progres';

    protected $fillable = [
        'id_penugasan',
        'persentase',
        'catatan_progres',
        'waktu_progres',
    ];

    protected function casts(): array
    {
        return [
            'waktu_progres' => 'datetime',
            'persentase' => 'integer',
        ];
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'id_penugasan', 'id_penugasan');
    }

    public function documents()
    {
        return $this->hasMany(RepairDocument::class, 'id_progres', 'id_progres');
    }

    /**
     * Validasi rentang persentase (0–100%) sesuai rancangan Class Diagram 4.4.5.
     */
    public static function isValidPercentage(int|float $persentase): bool
    {
        return $persentase >= 0 && $persentase <= 100;
    }
}
