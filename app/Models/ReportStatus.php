<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportStatus extends Model
{
    protected $table = 'report_statuses';
    protected $primaryKey = 'id_status';

    protected $fillable = [
        'nama_status',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class, 'id_status', 'id_status');
    }

    public function statusHistories()
    {
        return $this->hasMany(StatusHistory::class, 'id_status', 'id_status');
    }
}
