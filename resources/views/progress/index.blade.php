@extends('layouts.app')

@section('title', 'Progres Perbaikan')
@section('page_title', 'Progres Perbaikan')

@section('content')
    @php
        $isTeknisi = auth()->user()->hasRole('Teknisi');
    @endphp

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h3 class="text-base font-bold text-slate-900">Daftar Penugasan</h3>
            <p class="text-xs text-slate-500 mt-1">
                @if ($isTeknisi)
                    Catat dan perbarui tahapan perbaikan, kendala, serta perkembangan pekerjaan Anda.
                @else
                    Pantau perkembangan pekerjaan perbaikan yang sedang berjalan.
                @endif
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 self-start px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-600">
            {{ $assignments->count() }} penugasan
        </span>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
        @if ($assignments->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-3xl mb-3">📋</p>
                <p class="text-sm font-semibold text-slate-700">Belum ada penugasan</p>
                <p class="text-xs text-slate-500 mt-1">
                    @if ($isTeknisi)
                        Penugasan perbaikan untuk Anda akan muncul di sini setelah ditetapkan oleh Admin.
                    @else
                        Belum ada penugasan perbaikan yang dapat dipantau.
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100 bg-slate-50/60">
                            <th class="px-6 py-3 font-semibold">NO. LAPORAN</th>
                            <th class="px-4 py-3 font-semibold">PERANGKAT</th>
                            <th class="px-4 py-3 font-semibold">LOKASI</th>
                            @unless ($isTeknisi)
                                <th class="px-4 py-3 font-semibold">TEKNISI</th>
                            @endunless
                            <th class="px-4 py-3 font-semibold">STATUS LAPORAN</th>
                            <th class="px-4 py-3 font-semibold">PROGRES</th>
                            <th class="px-6 py-3 font-semibold text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($assignments as $assignment)
                            @php
                                $report = $assignment->report;
                                $latest = $assignment->latestProgress;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-800 whitespace-nowrap">
                                    {{ $report?->nomor_laporan ?? '-' }}
                                </td>
                                <td class="px-4 py-4">
                                    <span class="block text-slate-800 font-medium">{{ $report?->device?->nama_perangkat ?? '-' }}</span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">{{ $report?->device?->kode_aset }}</span>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    {{ $report?->room?->nama_ruangan ?? '-' }}
                                    @if ($report?->room?->building)
                                        <span class="block text-[11px] text-slate-400 mt-0.5">{{ $report->room->building->nama_gedung }}</span>
                                    @endif
                                </td>
                                @unless ($isTeknisi)
                                    <td class="px-4 py-4 text-slate-600">{{ $assignment->technician?->nama ?? '-' }}</td>
                                @endunless
                                <td class="px-4 py-4">
                                    <x-status-badge :status="$report?->status?->nama_status" />
                                </td>
                                <td class="px-4 py-4 min-w-[140px]">
                                    @if ($latest)
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-[11px] font-semibold text-slate-700">{{ $latest->persentase }}%</span>
                                            <span class="text-[10px] text-slate-400">{{ $assignment->progress_updates_count }} catatan</span>
                                        </div>
                                        <x-progress-bar :value="$latest->persentase" />
                                        <span class="block text-[10px] text-slate-400 mt-1.5">
                                            Diperbarui {{ $latest->waktu_progres?->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">Belum ada catatan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('progress.show', $assignment) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 text-[11px] font-semibold text-blue-700 hover:bg-blue-100 transition-colors">
                                        {{ $isTeknisi ? 'Kelola Progres' : 'Lihat Progres' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
