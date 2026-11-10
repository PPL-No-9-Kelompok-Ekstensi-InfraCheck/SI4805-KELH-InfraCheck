@extends('layouts.app')

@section('title', 'Ubah Jadwal Perbaikan')
@section('page_title', 'Ubah Jadwal Perbaikan')

@section('content')
<div class="max-w-3xl space-y-6">

    <div>
        <a href="{{ route('schedules.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors">
            &larr; Kembali ke Jadwal Perbaikan
        </a>
    </div>

    <!-- Informasi Penugasan -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Penugasan Terkait</p>
        <div class="mt-3 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">
                    {{ $schedule->assignment?->report?->device?->nama_perangkat }}
                    <span class="text-xs font-normal text-slate-500">({{ $schedule->assignment?->report?->device?->kode_aset }})</span>
                </h3>
                <p class="text-xs text-slate-600 mt-1">
                    📍 Lokasi: {{ $schedule->assignment?->report?->room?->nama_ruangan }} — {{ $schedule->assignment?->report?->room?->building?->nama_gedung }}
                </p>
                <p class="text-xs text-slate-500 mt-1">
                    Nomor Laporan: <span class="font-semibold text-slate-700">{{ $schedule->assignment?->report?->nomor_laporan ?? '-' }}</span>
                </p>
            </div>
            <div class="text-left sm:text-right">
                <span class="inline-block px-3 py-1 rounded-lg bg-blue-50 text-blue-800 text-xs font-bold border border-blue-200">
                    Teknisi: {{ $schedule->assignment?->technician?->nama ?? '-' }}
                </span>
                <p class="text-[11px] text-slate-400 mt-1">
                    Ditugaskan: {{ $schedule->assignment?->waktu_penugasan?->format('d/m/Y H:i') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Formulir Ubah Jadwal -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-7">
        <div class="mb-6 pb-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Perbarui Waktu & Catatan Pelaksanaan</h3>
            <p class="text-xs text-slate-500 mt-1">
                Ubah waktu mulai, perkiraan target selesai, atau catatan teknis pelaksanaan perbaikan ini.
            </p>
        </div>

        <form action="{{ route('schedules.update', $schedule) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Waktu Mulai & Target Selesai -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="waktu_mulai" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                        Waktu Mulai Perbaikan <span class="text-rose-600">*</span>
                    </label>
                    <input type="datetime-local"
                           name="waktu_mulai"
                           id="waktu_mulai"
                           value="{{ old('waktu_mulai', $schedule->waktu_mulai?->format('Y-m-d\TH:i')) }}"
                           required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-200 py-2.5 px-3">
                    @error('waktu_mulai')
                        <p class="text-xs text-rose-600 font-medium mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="target_selesai" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                        Target Selesai <span class="text-rose-600">*</span>
                    </label>
                    <input type="datetime-local"
                           name="target_selesai"
                           id="target_selesai"
                           value="{{ old('target_selesai', $schedule->target_selesai?->format('Y-m-d\TH:i')) }}"
                           required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-200 py-2.5 px-3">
                    @error('target_selesai')
                        <p class="text-xs text-rose-600 font-medium mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Catatan Jadwal -->
            <div>
                <label for="catatan_jadwal" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                    Catatan Jadwal <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                </label>
                <textarea name="catatan_jadwal"
                          id="catatan_jadwal"
                          rows="3"
                          placeholder="Tambahkan catatan koordinasi teknisi atau ruangan..."
                          class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-200 py-2.5 px-3">{{ old('catatan_jadwal', $schedule->catatan_jadwal) }}</textarea>
                @error('catatan_jadwal')
                    <p class="text-xs text-rose-600 font-medium mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('schedules.index') }}"
                   class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-lg bg-[#e11475] hover:bg-[#c90f66] text-white text-xs font-semibold shadow-sm transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Zona Pembatalan Jadwal -->
    <div class="bg-rose-50/60 rounded-xl border border-rose-200 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h4 class="text-sm font-bold text-rose-900">Batalkan Jadwal Perbaikan</h4>
            <p class="text-xs text-rose-700 mt-1">
                Jadwal akan dihapus dari kalender dan penugasan akan kembali berstatus belum terjadwal sehingga dapat dijadwalkan ulang nanti.
            </p>
        </div>

        <form action="{{ route('schedules.destroy', $schedule) }}" method="POST"
              onsubmit="return confirm('Apakah Anda yakin ingin membatalkan jadwal perbaikan ini? Jadwal akan dihapus dari kalender.');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors shadow-sm whitespace-nowrap">
                Batalkan Jadwal
            </button>
        </form>
    </div>

</div>
@endsection
