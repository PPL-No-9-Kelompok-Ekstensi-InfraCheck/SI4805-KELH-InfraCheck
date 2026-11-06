@extends('layouts.admin')

@section('title', 'Penugasan Laporan kepada Teknisi — InfraCheck')
@section('page_title', 'Penugasan Laporan kepada Teknisi')

@section('content')
@if (!$selectedReport)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-12 text-center max-w-2xl mx-auto">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="text-base font-bold text-slate-800 mb-1">Tidak Ada Laporan yang Perlu Ditugaskan</h3>
        <p class="text-xs text-slate-500 mb-5">
            Saat ini tidak ada laporan berstatus <span class="font-semibold text-blue-600">Diverifikasi</span> yang menunggu penugasan teknisi.
        </p>
        <a href="{{ route('admin.verification.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
            &larr; Buka Verifikasi Laporan
        </a>
    </div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">

        <!-- ==================== KOLOM KIRI: DETAIL LAPORAN ==================== -->
        <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200/80 p-7">
            
            <!-- Switcher jika ada banyak laporan diverifikasi -->
            @if ($verifiedReports->count() > 1)
                <div class="mb-5 pb-4 border-b border-slate-100 flex items-center justify-between gap-3">
                    <label for="report_select" class="text-xs font-semibold text-slate-500 shrink-0">Pilih Laporan:</label>
                    <select 
                        id="report_select" 
                        onchange="window.location.href='{{ route('admin.assignment.create') }}?report_id=' + this.value"
                        class="text-xs bg-slate-50 border border-slate-200 text-slate-700 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-rose-500 w-full max-w-[200px]"
                    >
                        @foreach ($verifiedReports as $r)
                            <option value="{{ $r->id_laporan }}" {{ $r->id_laporan === $selectedReport->id_laporan ? 'selected' : '' }}>
                                {{ $r->nomor_laporan }} - {{ $r->device->nama_perangkat ?? 'Perangkat' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <h3 class="text-base font-bold text-slate-900 mb-5">Laporan</h3>

            <div class="space-y-4 text-xs sm:text-sm">
                <!-- No. Laporan -->
                <div class="flex items-center justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400 font-medium">No. laporan</span>
                    <span class="text-slate-900 font-bold text-sm tracking-tight">{{ $selectedReport->nomor_laporan }}</span>
                </div>

                <!-- Perangkat -->
                <div class="flex items-center justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400 font-medium">Perangkat</span>
                    <span class="text-slate-800 font-semibold">{{ $selectedReport->device->nama_perangkat ?? '-' }}</span>
                </div>

                <!-- Kategori -->
                <div class="flex items-center justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400 font-medium">Kategori</span>
                    <span class="text-slate-800 font-medium">{{ $selectedReport->device->deviceType->category->nama_kategori ?? 'Infrastruktur' }}</span>
                </div>

                <!-- Lokasi -->
                <div class="flex items-center justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400 font-medium">Lokasi</span>
                    <span class="text-slate-800 font-medium">
                        {{ $selectedReport->room->nama_ruangan ?? '-' }}
                        @if ($selectedReport->room && $selectedReport->room->building)
                            , {{ $selectedReport->room->building->nama_gedung }}
                        @endif
                    </span>
                </div>

                <!-- Prioritas -->
                <div class="flex items-center justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400 font-medium">Prioritas</span>
                    <span class="font-bold text-[#e11d48]">
                        {{ $selectedReport->priority->nama_prioritas ?? 'Tinggi' }}
                    </span>
                </div>

                <!-- Status -->
                <div class="flex items-center justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400 font-medium">Status</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                        {{ $selectedReport->status->nama_status ?? 'Diverifikasi' }}
                    </span>
                </div>

                <!-- Status Penugasan Saat Ini (Jika ada) -->
                @if ($selectedReport->activeAssignment && $selectedReport->activeAssignment->technician)
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                        <span class="font-semibold">Penugasan Aktif:</span> Ditugaskan ke {{ $selectedReport->activeAssignment->technician->nama }}. Memilih teknisi baru akan mengalihkan tugas ini.
                    </div>
                @endif

                <!-- Deskripsi Kerusakan -->
                <div class="pt-2">
                    <span class="text-slate-400 font-medium block text-xs mb-1.5">Deskripsi</span>
                    <p class="text-xs sm:text-sm text-slate-800 leading-relaxed bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        {{ $selectedReport->deskripsi_kerusakan }}
                    </p>
                </div>
            </div>

        </div>

        <!-- ==================== KOLOM KANAN: PILIH TEKNISI ==================== -->
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200/80 p-7">
            
            <div class="mb-5">
                <h3 class="text-base font-bold text-slate-900">Pilih Teknisi</h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Diurutkan berdasarkan kesesuaian keahlian dengan kategori laporan.
                </p>
            </div>

            <!-- Form Penugasan -->
            <form action="{{ route('admin.assignment.store', $selectedReport->id_laporan) }}" method="POST">
                @csrf

                <!-- Daftar Teknisi -->
                <div class="space-y-3 mb-6" id="technicianList">
                    @forelse ($technicians as $index => $tech)
                        @php
                            $isSelected = old('id_teknisi', $selectedReport->activeAssignment->id_teknisi ?? ($index === 0 ? $tech->id_pengguna : null)) == $tech->id_pengguna;
                        @endphp
                        <label 
                            class="tech-card flex items-center justify-between p-4 rounded-2xl border transition-all cursor-pointer {{ $isSelected ? 'border-[#e11d48] bg-[#fff1f2]/30 shadow-sm ring-1 ring-[#e11d48]' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/70' }}"
                        >
                            <div class="flex items-center gap-3.5">
                                <!-- Radio Input -->
                                <input 
                                    type="radio" 
                                    name="id_teknisi" 
                                    value="{{ $tech->id_pengguna }}" 
                                    class="tech-radio w-4 h-4 text-[#e11d48] border-slate-300 focus:ring-[#e11d48]"
                                    {{ $isSelected ? 'checked' : '' }}
                                    required
                                >
                                <div>
                                    <p class="text-sm font-bold text-slate-900">
                                        {{ $tech->nama }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Keahlian: {{ $tech->keahlian }}
                                    </p>
                                </div>
                            </div>

                            <!-- Jumlah Tugas Aktif -->
                            <div class="text-right">
                                <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                    {{ $tech->active_tasks_count }} tugas aktif
                                </span>
                            </div>
                        </label>
                    @empty
                        <div class="p-6 text-center text-slate-400 text-xs">
                            Belum ada akun teknisi aktif yang terdaftar di sistem.
                        </div>
                    @endforelse
                </div>

                <!-- Catatan untuk Teknisi -->
                <div class="mb-6">
                    <label for="catatan_penugasan" class="block text-xs font-semibold text-slate-700 mb-2">
                        Catatan untuk teknisi
                    </label>
                    <textarea 
                        id="catatan_penugasan" 
                        name="catatan_penugasan" 
                        rows="3" 
                        placeholder="Contoh: Mohon dicek hari ini, ruang B-201 dipakai ujian besok."
                        class="w-full px-4 py-2.5 text-sm text-slate-800 bg-white border border-slate-300 rounded-xl placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#e11d48] focus:border-[#e11d48] transition-all"
                    >{{ old('catatan_penugasan') }}</textarea>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a 
                        href="{{ route('admin.verification.index') }}" 
                        class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold rounded-xl transition-colors"
                    >
                        Batal
                    </a>

                    <button 
                        type="submit" 
                        class="px-6 py-2.5 bg-[#e11d48] hover:bg-[#be123c] text-white text-sm font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-150 active:scale-[0.98]"
                    >
                        Tugaskan
                    </button>
                </div>

            </form>

        </div>

    </div>

    <!-- Script interaktif radio card -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const techCards = document.querySelectorAll('.tech-card');
            const radios = document.querySelectorAll('.tech-radio');

            function updateCardStyles() {
                techCards.forEach(card => {
                    const radio = card.querySelector('.tech-radio');
                    if (radio && radio.checked) {
                        card.classList.add('border-[#e11d48]', 'bg-[#fff1f2]/30', 'shadow-sm', 'ring-1', 'ring-[#e11d48]');
                        card.classList.remove('border-slate-200');
                    } else {
                        card.classList.remove('border-[#e11d48]', 'bg-[#fff1f2]/30', 'shadow-sm', 'ring-1', 'ring-[#e11d48]');
                        card.classList.add('border-slate-200');
                    }
                });
            }

            radios.forEach(radio => {
                radio.addEventListener('change', updateCardStyles);
            });

            techCards.forEach(card => {
                card.addEventListener('click', function () {
                    const radio = this.querySelector('.tech-radio');
                    if (radio) {
                        radio.checked = true;
                        updateCardStyles();
                    }
                });
            });
        });
    </script>
@endif
@endsection
