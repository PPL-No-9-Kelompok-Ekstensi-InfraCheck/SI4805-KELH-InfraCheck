@extends('layouts.app')

@section('title', 'Detail Progres Perbaikan')
@section('page_title', 'Progres Perbaikan')

@section('content')
    @php
        $report = $assignment->report;
        $latest = $assignment->progressUpdates->first();
    @endphp

    <div class="mb-6">
        <a href="{{ route('progress.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors">
            &larr; Kembali ke daftar penugasan
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

        <!-- ==================== KOLOM KIRI ==================== -->
        <div class="lg:col-span-2 space-y-7">

            <!-- Kartu Informasi Laporan -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nomor Laporan</p>
                        <h3 class="text-lg font-bold text-slate-900 mt-1">{{ $report?->nomor_laporan ?? '-' }}</h3>
                    </div>
                    <x-status-badge :status="$report?->status?->nama_status" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-6">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Perangkat</p>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ $report?->device?->nama_perangkat ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $report?->device?->kode_aset }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Lokasi</p>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ $report?->room?->nama_ruangan ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $report?->room?->building?->nama_gedung }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Pelapor</p>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ $report?->reporter?->nama ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $report?->waktu_laporan?->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Teknisi</p>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ $assignment->technician?->nama ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $assignment->technician?->unit }}</p>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Deskripsi Kerusakan</p>
                    <p class="text-sm text-slate-700 mt-1.5 leading-relaxed">{{ $report?->deskripsi_kerusakan ?? '-' }}</p>
                </div>
            </div>

            <!-- Kartu Riwayat Progres -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Riwayat Progres Perbaikan</h3>
                        <p class="text-xs text-slate-500 mt-1">{{ $assignment->progressUpdates->count() }} catatan tercatat</p>
                    </div>

                    @if ($canManage)
                        <a href="{{ route('progress.create', $assignment) }}"
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                            + Tambah Catatan Progres
                        </a>
                    @endif
                </div>

                @if ($assignment->progressUpdates->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center">
                        <p class="text-3xl mb-2">🛠️</p>
                        <p class="text-sm font-semibold text-slate-700">Belum ada catatan progres</p>
                        <p class="text-xs text-slate-500 mt-1">
                            @if ($canManage)
                                Mulai catat tahapan perbaikan agar Admin dan Pelapor dapat memantau pekerjaan.
                            @else
                                Teknisi belum menambahkan catatan progres untuk penugasan ini.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($assignment->progressUpdates as $progress)
                            <div class="rounded-xl border border-slate-200/80 border-l-4 border-l-blue-500 bg-slate-50/40 p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center min-w-[52px] px-2.5 py-1 rounded-lg bg-blue-600 text-white text-xs font-bold">
                                            {{ $progress->persentase }}%
                                        </span>
                                        <span class="text-xs font-semibold text-slate-600">
                                            {{ $progress->waktu_progres?->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    @if ($canManage)
                                        <a href="{{ route('progress.edit', [$assignment, $progress]) }}"
                                           class="self-start text-[11px] font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                                            Perbarui catatan
                                        </a>
                                    @endif
                                </div>

                                <p class="text-sm text-slate-700 mt-3 whitespace-pre-line leading-relaxed">{{ $progress->catatan_progres }}</p>

                                @if ($progress->documents->isNotEmpty())
                                    <p class="text-[11px] text-slate-500 mt-3">
                                        📎 {{ $progress->documents->count() }} dokumentasi terlampir
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- ==================== KOLOM KANAN ==================== -->
        <div class="space-y-7">

            <!-- Ringkasan Progres -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-5">Ringkasan Progres</h3>

                <p class="text-4xl font-extrabold text-slate-900">
                    {{ $latest?->persentase ?? 0 }}<span class="text-lg font-bold text-slate-400">%</span>
                </p>
                <p class="text-[11px] text-slate-500 mt-1">Progres terakhir</p>

                <x-progress-bar :value="$latest?->persentase ?? 0" class="mt-4 h-2.5" />

                <div class="mt-5 pt-5 border-t border-slate-100 space-y-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Jumlah catatan</span>
                        <span class="font-semibold text-slate-800">{{ $assignment->progressUpdates->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Pembaruan terakhir</span>
                        <span class="font-semibold text-slate-800">
                            {{ $latest?->waktu_progres?->format('d/m/Y H:i') ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Detail Penugasan -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-5">Detail Penugasan</h3>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Ditugaskan oleh</span>
                        <span class="font-semibold text-slate-800 text-right">{{ $assignment->assigningAdmin?->nama ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Tanggal penugasan</span>
                        <span class="font-semibold text-slate-800">{{ $assignment->waktu_penugasan?->format('d/m/Y H:i') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Status penugasan</span>
                        <span class="font-semibold text-slate-800 capitalize">{{ $assignment->status_penugasan ?? '-' }}</span>
                    </div>
                    @if ($assignment->schedule)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Jadwal perbaikan</span>
                            <span class="font-semibold text-slate-800 text-right">
                                {{ $assignment->schedule->waktu_mulai?->format('d/m/Y H:i') }}
                                &ndash;
                                {{ $assignment->schedule->target_selesai?->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @if ($canManage || auth()->user()->hasRole('Admin'))
                            <div class="pt-1 text-right">
                                <a href="{{ route('schedules.edit', $assignment->schedule) }}" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700">
                                    Kelola Jadwal &rarr;
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Jadwal perbaikan</span>
                            <span class="text-slate-400 italic">Belum diatur</span>
                        </div>
                        @if ($canManage || auth()->user()->hasRole('Admin'))
                            <div class="pt-1 text-right">
                                <a href="{{ route('schedules.create', ['assignment_id' => $assignment->id_penugasan]) }}" class="text-[11px] font-semibold text-[#e11475] hover:underline">
                                    + Atur Jadwal Sekarang
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
