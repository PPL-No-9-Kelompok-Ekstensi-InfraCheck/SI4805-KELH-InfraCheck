@php
    $report = $assignment->report;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

    <!-- Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <h3 class="text-base font-bold text-slate-900">Catatan Progres</h3>
            <p class="text-xs text-slate-500 mt-1">
                Catat tahapan perbaikan, kendala yang ditemukan, dan perkembangan pekerjaan.
            </p>

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800">
                    <p class="font-bold mb-1">Periksa kembali isian berikut:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ $action }}" method="POST" class="mt-6 space-y-5">
                @csrf
                @method($method)

                <div>
                    <label for="persentase" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Persentase Progres <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="persentase" id="persentase" min="0" max="100" step="1"
                               value="{{ old('persentase', $progress?->persentase ?? $assignment->latestProgress?->persentase ?? 0) }}"
                               class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                               required>
                        <span class="text-sm font-semibold text-slate-500">%</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Nilai 0 sampai 100 sesuai kondisi pekerjaan saat ini.</p>
                </div>

                <div>
                    <label for="waktu_progres" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Waktu Progres
                    </label>
                    <input type="datetime-local" name="waktu_progres" id="waktu_progres"
                           value="{{ old('waktu_progres', $progress?->waktu_progres?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                           class="w-full sm:w-72 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                    <p class="text-[11px] text-slate-400 mt-1">Kosongkan untuk menggunakan waktu saat ini.</p>
                </div>

                <div>
                    <label for="catatan_progres" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Catatan Progres <span class="text-red-500">*</span>
                    </label>
                    <textarea name="catatan_progres" id="catatan_progres" rows="7" maxlength="2000"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                              placeholder="Contoh: Pemeriksaan awal selesai. Kabel HDMI diganti, perangkat sudah menyala, namun warna masih pudar. Kendala: kipas proyektor berisik."
                              required>{{ old('catatan_progres', $progress?->catatan_progres) }}</textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Maksimal 2000 karakter.</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                        {{ $submitLabel }}
                    </button>
                    <a href="{{ route('progress.show', $assignment) }}"
                       class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Konteks Penugasan -->
    <div class="space-y-7">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <h3 class="text-sm font-bold text-slate-900 mb-5">Konteks Penugasan</h3>

            <div class="space-y-3 text-xs">
                <div>
                    <p class="text-slate-500">Nomor laporan</p>
                    <p class="font-semibold text-slate-800 mt-0.5">{{ $report?->nomor_laporan ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Perangkat</p>
                    <p class="font-semibold text-slate-800 mt-0.5">{{ $report?->device?->nama_perangkat ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Lokasi</p>
                    <p class="font-semibold text-slate-800 mt-0.5">
                        {{ $report?->room?->nama_ruangan ?? '-' }}
                        @if ($report?->room?->building)
                            &middot; {{ $report->room->building->nama_gedung }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-slate-500">Status laporan</p>
                    <p class="mt-1"><x-status-badge :status="$report?->status?->nama_status" /></p>
                </div>
            </div>
        </div>
    </div>
</div>
