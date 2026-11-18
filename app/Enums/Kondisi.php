<?php

namespace App\Enums;

enum Kondisi: string
{
    case Baik = 'Baik';
    case RusakRingan = 'Rusak Ringan';
    case RusakBerat = 'Rusak Berat';

    /**
     * Label kondisi untuk ditampilkan ke pengguna.
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Kelas badge Tailwind sesuai tingkat kerusakan.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Baik        => 'bg-green-100 text-green-700',
            self::RusakRingan => 'bg-amber-100 text-amber-700',
            self::RusakBerat  => 'bg-red-100 text-red-700',
        };
    }
}
