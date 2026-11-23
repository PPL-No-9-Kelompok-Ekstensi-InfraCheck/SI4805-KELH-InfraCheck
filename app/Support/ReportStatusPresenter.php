<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ReportStatusPresenter
{
    /**
     * Urutan status yang ditampilkan pada halaman pemantauan.
     *
     * @var list<string>
     */
    public const ORDER = [
        'Menunggu Verifikasi',
        'Diverifikasi',
        'Ditugaskan',
        'Sedang Dikerjakan',
        'Menunggu Validasi',
        'Selesai',
        'Ditolak',
    ];

    /**
     * Label UI untuk setiap status basis data.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        'Menunggu Verifikasi' => 'Diajukan',
        'Diverifikasi' => 'Diverifikasi',
        'Ditugaskan' => 'Ditugaskan',
        'Sedang Dikerjakan' => 'Dalam Perbaikan',
        'Menunggu Validasi' => 'Menunggu Validasi',
        'Selesai' => 'Selesai',
        'Ditolak' => 'Ditolak',
        'Dibatalkan' => 'Dibatalkan',
    ];

    /**
     * Kelas Tailwind untuk badge status.
     *
     * @var array<string, string>
     */
    public const BADGES = [
        'Menunggu Verifikasi' => 'border-slate-200 bg-slate-100 text-slate-700',
        'Diverifikasi' => 'border-blue-200 bg-blue-100 text-blue-700',
        'Ditugaskan' => 'border-purple-200 bg-purple-100 text-purple-700',
        'Sedang Dikerjakan' => 'border-amber-200 bg-amber-100 text-amber-700',
        'Menunggu Validasi' => 'border-cyan-200 bg-cyan-100 text-cyan-700',
        'Selesai' => 'border-emerald-200 bg-emerald-100 text-emerald-700',
        'Ditolak' => 'border-red-200 bg-red-100 text-red-700',
        'Dibatalkan' => 'border-slate-200 bg-slate-100 text-slate-500',
    ];

    /**
     * Warna garis atas kartu ringkasan.
     *
     * @var array<string, string>
     */
    public const CARD_BORDERS = [
        'Menunggu Verifikasi' => 'border-t-slate-400',
        'Diverifikasi' => 'border-t-blue-500',
        'Ditugaskan' => 'border-t-purple-500',
        'Sedang Dikerjakan' => 'border-t-amber-500',
        'Menunggu Validasi' => 'border-t-cyan-500',
        'Selesai' => 'border-t-emerald-500',
        'Ditolak' => 'border-t-red-500',
    ];

    /**
     * Warna teks prioritas.
     *
     * @var array<string, string>
     */
    public const PRIORITY_COLORS = [
        'Rendah' => 'text-emerald-600',
        'Sedang' => 'text-amber-600',
        'Tinggi' => 'text-red-600',
        'Mendesak' => 'text-rose-700',
    ];

    public static function label(?string $status): string
    {
        if ($status === null) {
            return '–';
        }

        return self::LABELS[$status] ?? $status;
    }

    public static function badge(?string $status): string
    {
        return self::BADGES[$status] ?? 'border-slate-200 bg-slate-100 text-slate-700';
    }

    public static function cardBorder(?string $status): string
    {
        return self::CARD_BORDERS[$status] ?? 'border-t-slate-400';
    }

    public static function priorityColor(?string $priority): string
    {
        if ($priority === null) {
            return 'text-slate-400';
        }

        return self::PRIORITY_COLORS[$priority] ?? 'text-slate-600';
    }

    public static function formatDate(mixed $date, string $format = 'd M Y, H:i'): string
    {
        if (blank($date)) {
            return '–';
        }

        return Carbon::parse($date)
            ->timezone('Asia/Jakarta')
            ->translatedFormat($format);
    }
}
