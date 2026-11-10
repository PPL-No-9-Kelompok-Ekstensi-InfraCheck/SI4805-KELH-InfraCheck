@extends('layouts.app')

@section('title', 'Atur Jadwal Perbaikan')
@section('page_title', 'Atur Jadwal Perbaikan')

@section('content')
<div class="max-w-3xl space-y-6">

    <div>
        <a href="{{ route('schedules.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors">
            &larr; Kembali ke Jadwal Perbaikan
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-7">
        <div class="mb-6 pb-5 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Formulir Penjadwalan Perbaikan</h3>
            <p class="text-xs text-slate-500 mt-1">
                Tentukan waktu mulai dan target penyelesaian perbaikan agar rencana penanganan tercatat dan terkoordinasi.
            </p>
        </div>

        <form action="{{ route('schedules.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Pemilihan Penugasan -->
            <div>
                <label for="id_penugasan" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                    Penugasan Perbaikan <span class="text-rose-600">*</span>
                </label>

                @if ($selectedAssignment)
                    <input type="hidden" name="id_penugasan" value="{{ $selectedAssignment->id_penugasan }}">
                    <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4">
                        <div class="flex items-center justify-between text-xs font-semibold text-blue-700">
                            <span>{{ $selectedAssignment->report?->nomor_laporan ?? 'Penugasan #' . $selectedAssignment->id_penugasan }}</span>
                            <span class="bg-blue-200/60 px-2.5 py-0.5 rounded text-blue-900 font-bold">
                                Teknisi: {{ $selectedAssignment->technician?->nama ?? '-' }}
                            </span>
                        </div>
                        <p class="text-sm font-bold text-slate-900 mt-1.5">
                            {{ $selectedAssignment->report?->device?->nama_perangkat }}
                            <span class="text-xs font-normal text-slate-500">({{ $selectedAssignment->report?->device?->kode_aset }})</span>
                        </p>
                        <p class="text-xs text-slate-600 mt-0.5">
                            📍 {{ $selectedAssignment->report?->room?->nama_ruangan }} — {{ $selectedAssignment->report?->room?->building?->nama_gedung }}
                        </p>
                        <p class="text-xs text-slate-500 mt-2 italic line-clamp-2">
                            "{{ $selectedAssignment->report?->deskripsi_kerusakan }}"
                        </p>
                    </div>
                @else
                    @if ($availableAssignments->isEmpty())
                        <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-4 text-xs text-amber-800">
                            Tidak ada penugasan aktif yang belum dijadwalkan saat ini. Semua penugasan telah memiliki jadwal atau belum ada penugasan baru yang ditugaskan kepada Anda.
                        </div>
                    @else
                        <select name="id_penugasan" id="id_penugasan" required
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-200 py-2.5 px-3">
                            <option value="">-- Pilih Penugasan yang Akan Dijadwalkan --</option>
                            @foreach ($availableAssignments as $assign)
                                <option value="{{ $assign->id_penugasan }}" {{ old('id_penugasan') == $assign->id_penugasan ? 'selected' : '' }}>
                                    {{ $assign->report?->nomor_laporan }} — {{ $assign->report?->device?->nama_perangkat }} ({{ $assign->report?->room?->nama_ruangan }}) [Teknisi: {{ $assign->technician?->nama }}]
                                </option>
                            @endforeach
                        </select>
                    @endif
                @endif

                @error('id_penugasan')
                    <p class="text-xs text-rose-600 font-medium mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            @php
                $initialTime = request('time') ? \Carbon\Carbon::parse(request('time')) : now()->addHour()->startOfHour();
                $defaultStart = old('waktu_mulai', $initialTime->format('Y-m-d\TH:i'));
                $defaultTarget = old('target_selesai', $initialTime->copy()->addHours(2)->format('Y-m-d\TH:i'));
            @endphp

            <!-- Waktu Mulai & Target Selesai -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="waktu_mulai" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                        Waktu Mulai Perbaikan <span class="text-rose-600">*</span>
                    </label>
                    <input type="datetime-local"
                           name="waktu_mulai"
                           id="waktu_mulai"
                           value="{{ $defaultStart }}"
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
                           value="{{ $defaultTarget }}"
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
                          placeholder="Contoh: Pengerjaan dilakukan setelah jam perkuliahan selesai (pukul 16:00 ke atas) atau koordinasikan dengan pengelola ruangan."
                          class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-200 py-2.5 px-3">{{ old('catatan_jadwal') }}</textarea>
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
                    Simpan Jadwal
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
