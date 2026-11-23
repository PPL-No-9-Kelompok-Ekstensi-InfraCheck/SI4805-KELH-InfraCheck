@extends('layouts.app')

@section('title', 'Dokumentasi Hasil Perbaikan')
@section('page_title', 'Dokumentasi Hasil Perbaikan')

@section('content')
@php
    $report = $assignment->report;
    $device = $report?->device;
    $room = $report?->room;
    $building = $room?->building;
    $firstProgress = $assignment->progressUpdates->sortBy('waktu_progres')->first();
@endphp

<!-- Breadcrumb -->
<div class="mb-5 flex items-center gap-2 text-xs text-slate-500 font-medium">
    <a href="{{ route('documents.index') }}" class="hover:text-blue-600 transition-colors">Tugas Saya</a>
    <span>&rsaquo;</span>
    <a href="{{ route('progress.show', $assignment) }}" class="hover:text-blue-600 transition-colors">{{ $report?->nomor_laporan ?? 'Laporan' }}</a>
    <span>&rsaquo;</span>
    <span class="text-slate-800 font-semibold">Dokumentasi</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

    <!-- ==================== KOLOM KIRI: FORM DOKUMENTASI ==================== -->
    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <!-- Header Ringkas Sesuai Mockup 4.5.20 -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 pb-5 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        {{ $report?->nomor_laporan ?? '-' }} &middot; {{ $device?->nama_perangkat ?? 'Perangkat' }} ({{ $device?->kode_aset }})
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">
                        📍 {{ $room?->nama_ruangan ?? '-' }}, {{ $building?->nama_gedung ?? '-' }}
                    </p>
                </div>
                <x-status-badge :status="$report?->status?->nama_status" />
            </div>

            @if ($isLocked)
                <div class="mt-5 p-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs flex items-center gap-3">
                    <span class="text-xl">🔒</span>
                    <div>
                        <p class="font-bold">Laporan telah berstatus Selesai</p>
                        <p class="text-emerald-700 mt-0.5">Dokumentasi hasil perbaikan telah terkunci dan tidak dapat diubah atau dihapus.</p>
                    </div>
                </div>
            @endif

            @if (! $canManage && ! $isLocked)
                <div class="mt-5 p-4 rounded-xl border border-blue-200 bg-blue-50 text-blue-800 text-xs flex items-center gap-3">
                    <span class="text-xl">ℹ️</span>
                    <div>
                        <p class="font-bold">Mode Tinjauan</p>
                        <p class="text-blue-700 mt-0.5">Anda sedang melihat dokumentasi hasil perbaikan. Pengelolaan hanya dapat dilakukan oleh teknisi penanggung jawab.</p>
                    </div>
                </div>
            @endif

            <!-- Formulir Unggah Dokumentasi -->
            @if ($canManage)
                <form action="{{ route('documents.store', $assignment) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf

                    @if ($errors->any())
                        <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-xs space-y-1">
                            <p class="font-bold">Terdapat kesalahan pada isian formulir:</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Bagian Foto Sebelum & Sesudah Sesuai Mockup 4.5.20 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Foto Sebelum Perbaikan -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-800">
                                Foto sebelum perbaikan <span class="text-red-500">*</span>
                            </label>
                            <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-4 text-center bg-slate-50/50 hover:bg-blue-50/20 transition-all cursor-pointer relative">
                                <input type="file" name="foto_sebelum[]" multiple accept="image/png,image/jpeg,image/jpg"
                                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                                       onchange="previewFiles(this, 'preview-sebelum')">
                                <div class="space-y-1 pointer-events-none py-3">
                                    <div class="text-2xl text-slate-400">📷</div>
                                    <p class="text-xs font-semibold text-slate-700">Unggah foto sebelum</p>
                                    <p class="text-[11px] text-slate-400">JPG/PNG maks. 2 MB</p>
                                </div>
                            </div>
                            <div id="preview-sebelum" class="flex flex-wrap gap-2 pt-1"></div>
                            @error('foto_sebelum')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('foto_sebelum.*')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Foto Sesudah Perbaikan -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-800">
                                Foto sesudah perbaikan <span class="text-red-500">*</span>
                            </label>
                            <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-4 text-center bg-slate-50/50 hover:bg-blue-50/20 transition-all cursor-pointer relative">
                                <input type="file" name="foto_sesudah[]" multiple accept="image/png,image/jpeg,image/jpg"
                                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                                       onchange="previewFiles(this, 'preview-sesudah')">
                                <div class="space-y-1 pointer-events-none py-3">
                                    <div class="text-2xl text-slate-400">📷</div>
                                    <p class="text-xs font-semibold text-slate-700">Unggah foto sesudah</p>
                                    <p class="text-[11px] text-slate-400">JPG/PNG maks. 2 MB</p>
                                </div>
                            </div>
                            <div id="preview-sesudah" class="flex flex-wrap gap-2 pt-1"></div>
                            @error('foto_sesudah')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('foto_sesudah.*')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Foto Selama Perbaikan (Opsional sesuai rancangan 4.3.5) -->
                    <div class="space-y-1.5 pt-1">
                        <label class="block text-xs font-semibold text-slate-700">
                            Foto selama perbaikan <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <input type="file" name="foto_selama[]" multiple accept="image/png,image/jpeg,image/jpg"
                               class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 border border-slate-200 rounded-lg p-1.5 bg-slate-50/30">
                        @error('foto_selama.*')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tindakan yang dilakukan * -->
                    <div class="space-y-1.5">
                        <label for="tindakan" class="block text-xs font-semibold text-slate-800">
                            Tindakan yang dilakukan <span class="text-red-500">*</span>
                        </label>
                        <textarea id="tindakan" name="tindakan" rows="3"
                                  placeholder="Contoh: Mengganti lampu proyektor dan membersihkan filter udara. Proyektor diuji 30 menit, tampilan normal."
                                  class="w-full text-xs rounded-lg border border-slate-200 p-3 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">{{ old('tindakan') }}</textarea>
                        @error('tindakan')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Baris Suku Cadang & Kondisi Perangkat -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Suku Cadang -->
                        <div class="space-y-1.5">
                            <label for="suku_cadang" class="block text-xs font-semibold text-slate-800">
                                Suku cadang yang diganti <span class="text-slate-400 font-normal">(jika ada)</span>
                            </label>
                            <input type="text" id="suku_cadang" name="suku_cadang" value="{{ old('suku_cadang') }}"
                                   placeholder="Contoh: Lampu proyektor (1 unit)"
                                   class="w-full text-xs rounded-lg border border-slate-200 p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                            @error('suku_cadang')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kondisi Perangkat Setelah Perbaikan -->
                        <div class="space-y-1.5">
                            <label for="kondisi_perangkat" class="block text-xs font-semibold text-slate-800">
                                Kondisi perangkat setelah perbaikan
                            </label>
                            <select id="kondisi_perangkat" name="kondisi_perangkat"
                                    class="w-full text-xs rounded-lg border border-slate-200 p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                                @php
                                    $kondisiCurrent = old('kondisi_perangkat', $device?->kondisi?->value ?? 'Baik');
                                @endphp
                                <option value="Baik" @selected($kondisiCurrent === 'Baik')>Baik</option>
                                <option value="Rusak Ringan" @selected($kondisiCurrent === 'Rusak Ringan')>Rusak Ringan</option>
                                <option value="Rusak Berat" @selected($kondisiCurrent === 'Rusak Berat')>Rusak Berat</option>
                            </select>
                            @error('kondisi_perangkat')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Tombol Aksi Sesuai Mockup 4.5.20 -->
                    <div class="pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-end gap-3">
                        <button type="submit" name="tandai_selesai" value="0"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-xs transition-colors">
                            Simpan Draf
                        </button>
                        <button type="submit" name="tandai_selesai" value="1"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-sm transition-colors">
                            Simpan &amp; Tandai Selesai
                        </button>
                    </div>
                </form>
            @endif
        </div>

    </div>

    <!-- ==================== KOLOM KANAN: RINGKASAN & GALERI FOTO ==================== -->
    <div class="space-y-6">

        <!-- Card Ringkasan Tugas Sesuai Mockup 4.5.20 -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Ringkasan Tugas</h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Ditugaskan</span>
                    <span class="font-semibold text-slate-800">
                        {{ $assignment->waktu_penugasan?->translatedFormat('d M Y') ?? '-' }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Mulai dikerjakan</span>
                    <span class="font-semibold text-slate-800">
                        {{ $firstProgress?->waktu_progres?->translatedFormat('d M Y') ?? ($assignment->schedule?->waktu_mulai?->translatedFormat('d M Y') ?? '-') }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Prioritas</span>
                    <span class="font-semibold text-slate-800">
                        {{ $report?->priority?->nama_prioritas ?? 'Sedang' }}
                    </span>
                </div>
            </div>

            <!-- Banner Notifikasi Mockup 4.5.20 -->
            <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-3.5 text-xs text-blue-900 leading-relaxed">
                Setelah ditandai selesai, pelapor akan diminta memberikan tanggapan atas hasil perbaikan.
            </div>
        </div>

        <!-- Galeri Dokumentasi Hasil Perbaikan yang Tersimpan -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Dokumentasi Tersimpan</h3>
                <span class="text-xs text-slate-500 font-medium">
                    {{ $existingDocuments->flatten()->count() }} berkas
                </span>
            </div>

            @if ($existingDocuments->isEmpty())
                <div class="py-8 text-center text-slate-400">
                    <p class="text-2xl mb-1">🖼️</p>
                    <p class="text-xs">Belum ada foto dokumentasi yang diunggah.</p>
                </div>
            @else
                @foreach (['Sebelum', 'Selama', 'Sesudah'] as $jenis)
                    @php
                        $docs = $existingDocuments->get($jenis, collect());
                    @endphp
                    @if ($docs->isNotEmpty())
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $jenis === 'Sebelum' ? 'bg-amber-500' : ($jenis === 'Sesudah' ? 'bg-emerald-500' : 'bg-blue-500') }}"></span>
                                    Foto {{ $jenis }} Perbaikan ({{ $docs->count() }})
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($docs as $doc)
                                    <div class="group relative rounded-lg border border-slate-200 overflow-hidden bg-slate-50">
                                        <a href="{{ $doc->url }}" target="_blank" class="block aspect-video bg-slate-200 overflow-hidden">
                                            <img src="{{ $doc->url }}" alt="Dokumentasi {{ $jenis }}"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                        </a>

                                        <div class="p-2 bg-white flex items-center justify-between text-[10px] text-slate-500">
                                            <span>{{ $doc->waktu_unggah?->format('d/m H:i') }}</span>

                                            @if ($canManage && ! $isLocked)
                                                <div class="flex items-center gap-1">
                                                    <!-- Tombol Ganti -->
                                                    <button type="button"
                                                            onclick="bukaModalGanti('{{ route('documents.update', $doc) }}', '{{ $jenis }}')"
                                                            class="text-blue-600 hover:text-blue-800 font-semibold px-1 py-0.5 rounded hover:bg-blue-50"
                                                            title="Ganti berkas">
                                                        Ganti
                                                    </button>
                                                    &middot;
                                                    <!-- Tombol Hapus -->
                                                    <form action="{{ route('documents.destroy', $doc) }}" method="POST"
                                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto dokumentasi ini?');"
                                                          class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold px-1 py-0.5 rounded hover:bg-red-50"
                                                                title="Hapus berkas">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

    </div>

</div>

<!-- Modal Ganti Berkas -->
<div id="modal-ganti-berkas" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h4 class="text-sm font-bold text-slate-900">Ganti Foto Dokumentasi</h4>
            <button type="button" onclick="tutupModalGanti()" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <form id="form-ganti-berkas" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <p class="text-xs text-slate-600" id="modal-jenis-info">
                Pilih foto baru pengganti untuk dokumentasi perbaikan ini (JPG/PNG maks. 2 MB).
            </p>

            <input type="file" name="berkas" required accept="image/png,image/jpeg,image/jpg"
                   class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-lg p-1.5">

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="tutupModalGanti()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function previewFiles(input, containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-12 h-12 rounded object-cover border border-slate-200 shadow-xs';
                    container.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    }

    function bukaModalGanti(actionUrl, jenis) {
        const modal = document.getElementById('modal-ganti-berkas');
        const form = document.getElementById('form-ganti-berkas');
        form.action = actionUrl;
        document.getElementById('modal-jenis-info').textContent = 'Pilih foto baru pengganti untuk dokumentasi ' + jenis + ' (JPG/PNG maks. 2 MB).';
        modal.classList.remove('hidden');
    }

    function tutupModalGanti() {
        document.getElementById('modal-ganti-berkas').classList.add('hidden');
    }
</script>
@endpush
@endsection
