@extends('layouts.app')

@section('title', 'Pembaruan Progres Perbaikan')
@section('page_title', 'Pembaruan Progres Perbaikan')

@section('content')
@php
    $report = $assignment->report;
    $latest = $assignment->latestProgress ?? $assignment->progressUpdates->first();
    $percentage = $latest?->persentase ?? 0;
    $statusName = $report?->status?->nama_status ?? 'Ditugaskan';

    // Logika tahapan Stepper: Ditugaskan (1) ➔ Dalam Perbaikan (2) ➔ Menunggu Validasi (3)
    $step1Done = true; // Ditugaskan selalu tercapai jika sudah ada penugasan
    $step2Done = $percentage > 0 || in_array($statusName, ['Sedang Dikerjakan', 'Dalam Perbaikan', 'Menunggu Validasi', 'Selesai']);
    $step3Done = $percentage >= 100 || in_array($statusName, ['Menunggu Validasi', 'Selesai']);

    $firstProgressDate = $assignment->progressUpdates->sortBy('waktu_progres')->first()?->waktu_progres;
@endphp

<div class="mb-5">
    <a href="{{ route('progress.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors">
        &larr; Tugas Saya
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

    <!-- ==================== KOLOM KIRI: FORM PROGRES & STEPPER ==================== -->
    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <!-- Header Informasi Penugasan -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 pb-6 border-b border-slate-100">
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">
                        Tugas Saya &rsaquo; {{ $report?->nomor_laporan ?? 'LP-2026-0142' }}
                    </span>
                    <h3 class="text-xl font-bold text-slate-900 mt-1">
                        {{ $report?->nomor_laporan ?? 'LP-2026-0142' }} · {{ $report?->device?->nama_perangkat ?? 'Perangkat' }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kode Aset: <span class="font-semibold text-slate-700">{{ $report?->device?->kode_aset ?? '-' }}</span>
                    </p>
                </div>
                <div class="self-start">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $step3Done ? 'bg-emerald-100 text-emerald-800' : ($step2Done ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800') }}">
                        {{ $statusName }}
                    </span>
                </div>
            </div>

            <!-- Stepper Tahapan (Sesuai Mockup Gambar 4.5.18) -->
            <div class="py-6 px-4">
                <div class="flex items-center justify-between relative max-w-xl mx-auto">
                    <!-- Garis Penghubung Step 1 ke 2 -->
                    <div class="absolute left-10 right-1/2 top-4 -translate-y-1/2 h-1 {{ $step2Done ? 'bg-pink-600' : 'bg-slate-200' }} -z-0"></div>
                    <!-- Garis Penghubung Step 2 ke 3 -->
                    <div class="absolute left-1/2 right-10 top-4 -translate-y-1/2 h-1 {{ $step3Done ? 'bg-pink-600' : 'bg-slate-200' }} -z-0"></div>

                    <!-- Step 1: Ditugaskan -->
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-full bg-pink-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                            1
                        </div>
                        <p class="text-xs font-bold text-slate-800 mt-2">Ditugaskan</p>
                        <p class="text-[10px] text-slate-500">{{ $assignment->waktu_penugasan?->format('d M, H:i') ?? '-' }}</p>
                    </div>

                    <!-- Step 2: Dalam Perbaikan -->
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-full {{ $step2Done ? 'bg-pink-600 text-white' : 'bg-slate-200 text-slate-600' }} flex items-center justify-center font-bold text-xs shadow-sm">
                            2
                        </div>
                        <p class="text-xs font-bold {{ $step2Done ? 'text-slate-800' : 'text-slate-400' }} mt-2">Dalam Perbaikan</p>
                        <p class="text-[10px] text-slate-500">{{ $firstProgressDate?->format('d M, H:i') ?? ($step2Done ? 'Aktif' : '-') }}</p>
                    </div>

                    <!-- Step 3: Menunggu Validasi -->
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-full {{ $step3Done ? 'bg-pink-600 text-white' : 'bg-slate-200 text-slate-600' }} flex items-center justify-center font-bold text-xs shadow-sm">
                            3
                        </div>
                        <p class="text-xs font-bold {{ $step3Done ? 'text-slate-800' : 'text-slate-400' }} mt-2">Menunggu Validasi</p>
                        <p class="text-[10px] text-slate-500">{{ $percentage >= 100 ? 'Siap diverifikasi' : '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Formulir Pembaruan Progres (Khusus Teknisi Ditugaskan) -->
            @if ($canManage)
                <div class="pt-6 border-t border-slate-100">
                    <h4 class="text-sm font-bold text-slate-900 mb-4">Input Progres Perbaikan</h4>

                    <form action="{{ route('progress.store', $assignment) }}" method="POST" class="space-y-5">
                        @csrf

                        <!-- Status baru & Persentase Progres -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="status_baru" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Status Baru
                                </label>
                                <select id="status_baru" name="status_baru" class="w-full text-sm rounded-lg border-slate-300 py-2.5 px-3 bg-slate-50/50 focus:border-blue-500 focus:ring-blue-200">
                                    <option value="Sedang Dikerjakan" {{ $percentage < 100 ? 'selected' : '' }}>Dalam Perbaikan / Sedang Dikerjakan</option>
                                    <option value="Menunggu Validasi" {{ $percentage >= 100 ? 'selected' : '' }}>Menunggu Validasi Admin (Selesai Pengerjaan)</option>
                                </select>
                            </div>

                            <div>
                                <label for="persentase" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Persentase Progres (0 – 100%) <span class="text-rose-600">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number"
                                           name="persentase"
                                           id="persentase"
                                           min="0"
                                           max="100"
                                           value="{{ old('persentase', $percentage) }}"
                                           required
                                           class="w-full text-sm rounded-lg border-slate-300 py-2.5 px-3 pr-8 focus:border-blue-500 focus:ring-blue-200">
                                    <span class="absolute right-3 top-2.5 text-slate-400 font-bold text-sm">%</span>
                                </div>
                                @error('persentase')
                                    <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Catatan Progres -->
                        <div>
                            <label for="catatan_progres" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Catatan Progres <span class="text-rose-600">*</span>
                            </label>
                            <textarea name="catatan_progres"
                                      id="catatan_progres"
                                      rows="3"
                                      required
                                      placeholder="Tuliskan tindakan yang dilakukan atau kendala suku cadang..."
                                      class="w-full text-sm rounded-lg border-slate-300 py-2.5 px-3 focus:border-blue-500 focus:ring-blue-200">{{ old('catatan_progres') }}</textarea>
                            @error('catatan_progres')
                                <p class="text-xs text-rose-600 font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('progress.index') }}"
                               class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                Batal
                            </a>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-lg bg-[#e11475] hover:bg-[#c90f66] text-white text-xs font-semibold shadow-sm transition-colors">
                                Simpan Progres
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

    </div>

    <!-- ==================== KOLOM KANAN: RIWAYAT & INFO LAPORAN ==================== -->
    <div class="space-y-6">

        <!-- Catatan Progres Sebelumnya (Sesuai Mockup Gambar 4.5.18) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">
                Catatan Progres Sebelumnya
            </h3>

            @if ($assignment->progressUpdates->isEmpty())
                <p class="text-xs text-slate-400 italic py-3 text-center">Belum ada catatan progres sebelumnya.</p>
            @else
                <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
                    @foreach ($assignment->progressUpdates as $prog)
                        <div class="text-xs space-y-1 pb-3 border-b border-slate-100 last:border-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800">
                                    {{ $prog->waktu_progres?->format('d M Y, H:i') }}
                                </span>
                                <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">
                                    {{ $prog->persentase }}%
                                </span>
                            </div>
                            <p class="text-slate-600 leading-relaxed">{{ $prog->catatan_progres }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Info Laporan (Sesuai Mockup Gambar 4.5.18) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">
                Info Laporan
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Lokasi</span>
                    <span class="font-semibold text-slate-800 text-right">
                        {{ $report?->room?->building?->nama_gedung ?? 'Gedung' }}, {{ $report?->room?->nama_ruangan ?? '-' }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Prioritas</span>
                    <span class="font-bold text-amber-600">
                        {{ $report?->priority?->nama_prioritas ?? 'Sedang' }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Jadwal</span>
                    <span class="font-semibold text-slate-800">
                        {{ $assignment->schedule?->waktu_mulai?->format('d M, H:i') ?? '-' }}
                    </span>
                </div>

                @if ($assignment->schedule)
                    <div class="pt-2 text-right">
                        <a href="{{ route('schedule.index') }}" class="text-[11px] font-semibold text-blue-600 hover:underline">
                            Lihat di Kalender Jadwal &rarr;
                        </a>
                    </div>
                @else
                    <div class="pt-2 text-right">
                        <a href="{{ route('schedule.index') }}" class="text-[11px] font-semibold text-pink-600 hover:underline">
                            + Atur Jadwal
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
