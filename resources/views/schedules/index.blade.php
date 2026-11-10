@extends('layouts.app')

@section('title', 'Penjadwalan Perbaikan')
@section('page_title', 'Penjadwalan Perbaikan')

@section('content')
<div class="space-y-6">

    <!-- ==================== HEADER FILTER BAR ==================== -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <!-- Navigasi Minggu -->
        <div class="flex items-center gap-2">
            <a href="{{ route('schedules.index', ['week' => $prevWeek, 'technician_id' => $selectedTechnicianId]) }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm"
               title="Minggu Sebelumnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>

            <div class="px-3.5 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm text-sm font-bold text-slate-800 tracking-tight select-none">
                {{ $weekLabel }}
            </div>

            <a href="{{ route('schedules.index', ['week' => $nextWeek, 'technician_id' => $selectedTechnicianId]) }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm"
               title="Minggu Berikutnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            @if ($baseDate->format('Y-m-d') !== now()->format('Y-m-d'))
                <a href="{{ route('schedules.index', ['week' => $currentWeek, 'technician_id' => $selectedTechnicianId]) }}"
                   class="ml-1 text-xs font-semibold text-blue-600 hover:text-blue-800 underline">
                    Minggu Ini
                </a>
            @endif
        </div>

        <!-- Filter Teknisi & Tombol Atur Jadwal -->
        <div class="flex items-center gap-3">
            @if (auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Pimpinan'))
                <form method="GET" action="{{ route('schedules.index') }}" class="flex items-center">
                    <input type="hidden" name="week" value="{{ $baseDate->format('Y-m-d') }}">
                    <select name="technician_id"
                            onchange="this.form.submit()"
                            class="text-sm rounded-lg border-slate-200 bg-white text-slate-700 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 py-2 px-3 pr-8 font-medium">
                        <option value="">Semua teknisi</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id_pengguna }}" {{ (string)$selectedTechnicianId === (string)$tech->id_pengguna ? 'selected' : '' }}>
                                {{ $tech->nama }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if ($canManage)
                <a href="{{ route('schedules.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#e11475] hover:bg-[#c90f66] text-white text-sm font-semibold transition-colors shadow-sm">
                    <span>+ Atur Jadwal</span>
                </a>
            @endif
        </div>
    </div>

    <!-- ==================== TABEL KALENDER MINGGUAN ==================== -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-200">
                        <th class="w-20 px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200">
                            JAM
                        </th>
                        @foreach ($days as $day)
                            <th class="w-[18.4%] px-4 py-3.5 text-center text-xs font-bold text-slate-700 uppercase tracking-wider border-r last:border-r-0 border-slate-200 {{ $day['is_today'] ? 'bg-blue-50/50 text-blue-800' : '' }}">
                                {{ $day['label'] }}
                                @if ($day['is_today'])
                                    <span class="ml-1 inline-block w-2 h-2 rounded-full bg-blue-600" title="Hari Ini"></span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($hourSlots as $slot)
                        <tr class="hover:bg-slate-50/20 transition-colors">
                            <!-- Kolom Jam -->
                            <td class="w-20 py-4 px-2 text-center text-xs font-medium text-slate-500 bg-slate-50/30 border-r border-slate-200 select-none align-middle">
                                {{ $slot }}
                            </td>

                            <!-- 5 Kolom Hari (Senin - Jumat) -->
                            @foreach ($days as $day)
                                @php
                                    $dayKey = $day['date'];
                                    $items = $gridSchedules[$dayKey][$slot] ?? [];
                                @endphp
                                <td class="px-2.5 py-2.5 border-r last:border-r-0 border-slate-200 align-top min-h-[68px] {{ $day['is_today'] ? 'bg-blue-50/10' : '' }}">
                                    @if (!empty($items))
                                        <div class="space-y-1.5">
                                            @foreach ($items as $sched)
                                                @php
                                                    $tech = $sched->assignment?->technician;
                                                    $color = $colorStyles[$tech?->id_pengguna ?? 0] ?? [
                                                        'bg' => 'bg-purple-100/90',
                                                        'border' => 'border-purple-600',
                                                        'text' => 'text-purple-900',
                                                        'dot' => 'bg-purple-600'
                                                    ];
                                                    $deviceCode = $sched->assignment?->report?->device?->kode_aset ?? 'Perangkat';
                                                    $techFirstName = explode(' ', trim($tech?->nama ?? 'Teknisi'))[0];
                                                @endphp

                                                <a href="{{ $canManage ? route('schedules.edit', $sched) : '#' }}"
                                                   class="group block rounded-md {{ $color['bg'] }} border-l-4 {{ $color['border'] }} px-2.5 py-1.5 shadow-sm transition-all hover:shadow hover:scale-[1.01]"
                                                   title="{{ $tech?->nama }} | {{ $sched->assignment?->report?->device?->nama_perangkat }} ({{ $sched->waktu_mulai?->format('H:i') }} - {{ $sched->target_selesai?->format('H:i') }})">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="text-xs font-bold {{ $color['text'] }} truncate">
                                                            {{ $techFirstName }} · {{ $deviceCode }}
                                                        </span>
                                                        <span class="text-[10px] text-slate-500 font-medium whitespace-nowrap">
                                                            {{ $sched->waktu_mulai?->format('H:i') }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-600 truncate mt-0.5">
                                                        {{ $sched->assignment?->report?->room?->nama_ruangan ?? '-' }}
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <!-- Slot Kosong -->
                                        @if ($canManage)
                                            <a href="{{ route('schedules.create') }}?time={{ $day['date'] }} {{ $slot }}"
                                               class="block w-full h-8 rounded border border-transparent hover:border-dashed hover:border-slate-300 hover:bg-slate-50/50 transition-colors"
                                               title="Klik untuk jadwalkan perbaikan di slot ini">
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Catatan Keterangan Mockup -->
    <p class="text-xs text-slate-500 italic pl-1">
        Klik jadwal untuk mengubah waktu atau teknisi. Jadwal otomatis muncul di menu <span class="font-semibold text-slate-700">Jadwal Saya</span> milik teknisi.
    </p>

    <!-- ==================== ANTREAN PENUGASAN BELUM DIJADWALKAN ==================== -->
    @if ($canManage && $unscheduledAssignments->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Penugasan Menunggu Penjadwalan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Terdapat {{ $unscheduledAssignments->count() }} penugasan aktif yang belum memiliki jadwal pelaksanaan perbaikan.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($unscheduledAssignments as $assignment)
                    <div class="rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                                <span>{{ $assignment->report?->nomor_laporan ?? 'LAPORAN' }}</span>
                                <span class="capitalize text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                    {{ $assignment->technician?->nama ?? 'Teknisi' }}
                                </span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800 mt-2 line-clamp-1">
                                {{ $assignment->report?->device?->nama_perangkat ?? 'Perangkat' }}
                            </h4>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                📍 {{ $assignment->report?->room?->nama_ruangan }} ({{ $assignment->report?->room?->building?->nama_gedung }})
                            </p>
                            <p class="text-xs text-slate-600 mt-2 line-clamp-2">
                                {{ $assignment->report?->deskripsi_kerusakan }}
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                            <a href="{{ route('progress.show', $assignment) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                                Detail Tugas &rarr;
                            </a>
                            <a href="{{ route('schedules.create', ['assignment_id' => $assignment->id_penugasan]) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#e11475] hover:bg-[#c90f66] text-white text-xs font-semibold shadow-sm transition-colors">
                                + Jadwalkan
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
