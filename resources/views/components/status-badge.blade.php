@props(['status' => ''])

@php
    $colors = [
        'Menunggu Verifikasi' => 'bg-slate-100 text-slate-700 border-slate-200',
        'Diverifikasi' => 'bg-blue-100 text-blue-700 border-blue-200',
        'Ditolak' => 'bg-red-100 text-red-700 border-red-200',
        'Ditugaskan' => 'bg-purple-100 text-purple-700 border-purple-200',
        'Sedang Dikerjakan' => 'bg-amber-100 text-amber-700 border-amber-200',
        'Menunggu Validasi' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
        'Selesai' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'Dibatalkan' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];

    $classes = $colors[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

<span {{ $attributes->merge(['class' => "inline-block px-3 py-0.5 rounded-full text-[11px] font-semibold border $classes"]) }}>
    {{ $status !== '' ? $status : '-' }}
</span>
