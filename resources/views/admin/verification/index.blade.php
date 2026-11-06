@extends('layouts.admin')

@section('title', 'Verifikasi Laporan Kerusakan — InfraCheck')
@section('page_title', 'Verifikasi Laporan Kerusakan')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">

    <!-- ==================== KOLOM KIRI: DAFTAR MENUNGGU VERIFIKASI ==================== -->
    <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5">
        <h3 class="text-base font-bold text-slate-900 mb-4 px-1">
            Menunggu Verifikasi ({{ $pendingCount }})
        </h3>

        @if ($pendingReports->isEmpty())
            <div class="p-6 text-center text-slate-400 text-xs">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Semua laporan telah diverifikasi.
            </div>
        @else
            <div class="space-y-2.5">
                @foreach ($pendingReports as $report)
                    @php
                        $isActive = $selectedReport && $selectedReport->id_laporan === $report->id_laporan;
                    @endphp
                    <a 
                        href="{{ route('admin.verification.index', ['selected' => $report->id_laporan]) }}" 
                        class="block p-3.5 rounded-xl transition-all {{ $isActive ? 'bg-[#fff1f2] border-l-4 border-l-[#e11d48] shadow-sm' : 'hover:bg-slate-50 border border-slate-100' }}"
                    >
                        <p class="text-sm font-bold {{ $isActive ? 'text-[#9f1239]' : 'text-slate-900' }}">
                            {{ $report->nomor_laporan }}
                        </p>
                        <p class="text-xs font-medium text-slate-700 mt-0.5 truncate">
                            {{ $report->device->nama_perangkat ?? 'Perangkat Tanpa Nama' }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1 truncate">
                            {{ $report->room->nama_ruangan ?? 'Ruangan' }} · {{ $report->waktu_laporan ? $report->waktu_laporan->translatedFormat('d M') : '-' }}
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ==================== KOLOM KANAN: DETAIL LAPORAN TERPILIH ==================== -->
    <div class="lg:col-span-8">
        @if ($selectedReport)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-7">
                
                <!-- Header Laporan -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-3">
                    <h3 class="text-lg font-bold text-slate-900">
                        {{ $selectedReport->nomor_laporan }} · {{ $selectedReport->device->nama_perangkat ?? 'Perangkat' }}
                    </h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 w-fit">
                        {{ $selectedReport->status->nama_status ?? 'Diajukan' }}
                    </span>
                </div>

                <!-- Informasi Detail Pelapor & Lokasi -->
                <div class="py-5 border-b border-slate-100 space-y-3.5 text-xs sm:text-sm">
                    <div class="flex flex-col sm:flex-row sm:justify-between">
                        <span class="text-slate-500 font-medium w-36">Pelapor</span>
                        <span class="text-slate-800 font-medium">
                            {{ $selectedReport->reporter->nama ?? '-' }} 
                            @if ($selectedReport->reporter && $selectedReport->reporter->unit)
                                ({{ $selectedReport->reporter->unit }})
                            @elseif ($selectedReport->reporter && $selectedReport->reporter->role)
                                ({{ $selectedReport->reporter->role->nama_peran }})
                            @endif
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between">
                        <span class="text-slate-500 font-medium w-36">Lokasi</span>
                        <span class="text-slate-800 font-medium">
                            {{ $selectedReport->room->nama_ruangan ?? '-' }}
                            @if ($selectedReport->room && $selectedReport->room->building)
                                · {{ $selectedReport->room->building->nama_gedung }}
                            @endif
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between">
                        <span class="text-slate-500 font-medium w-36">Dilaporkan</span>
                        <span class="text-slate-800 font-medium">
                            {{ $selectedReport->waktu_laporan ? $selectedReport->waktu_laporan->translatedFormat('d M Y, H:i') : '-' }}
                        </span>
                    </div>
                </div>

                <!-- Deskripsi Kerusakan -->
                <div class="py-5 border-b border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deskripsi</p>
                    <p class="text-sm text-slate-800 leading-relaxed">
                        {{ $selectedReport->deskripsi_kerusakan }}
                    </p>

                    <!-- Preview Foto Dokumentasi -->
                    <div class="mt-4 flex flex-wrap gap-4">
                        @forelse ($selectedReport->photos as $index => $photo)
                            <div class="w-36 h-28 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 text-xs font-medium overflow-hidden relative group">
                                @if (file_exists(public_path('storage/' . $photo->lokasi_berkas)))
                                    <img src="{{ asset('storage/' . $photo->lokasi_berkas) }}" alt="Foto Kerusakan" class="w-full h-full object-cover">
                                @else
                                    <div class="text-center p-2">
                                        <svg class="w-6 h-6 mx-auto text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        foto {{ $index + 1 }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="w-36 h-28 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 text-xs font-medium">
                                foto 1
                            </div>
                            <div class="w-36 h-28 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 text-xs font-medium">
                                foto 2
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Formulir Keputusan Verifikasi / Tolak -->
                <form id="verificationForm" method="POST" class="pt-5">
                    @csrf
                    <div class="mb-5">
                        <label for="catatan_verifikasi" class="block text-xs font-semibold text-slate-700 mb-2">
                            Catatan verifikasi / alasan penolakan
                        </label>
                        <textarea 
                            id="catatan_verifikasi" 
                            name="catatan_verifikasi" 
                            rows="3" 
                            placeholder="Wajib diisi jika laporan ditolak"
                            class="w-full px-4 py-2.5 text-sm text-slate-800 bg-white border border-slate-300 rounded-xl placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        >{{ old('catatan_verifikasi') }}</textarea>
                    </div>

                    <!-- Tombol Aksi (Tolak & Verifikasi Laporan) -->
                    <div class="flex items-center justify-end gap-3">
                        <!-- Tombol Tolak -->
                        <button 
                            type="submit" 
                            formaction="{{ route('admin.verification.reject', $selectedReport->id_laporan) }}"
                            onclick="return confirmReject()"
                            class="px-5 py-2.5 bg-[#dc2626] hover:bg-[#b91c1c] text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-150 active:scale-[0.98]"
                        >
                            Tolak
                        </button>

                        <!-- Tombol Verifikasi Laporan -->
                        <button 
                            type="submit" 
                            formaction="{{ route('admin.verification.verify', $selectedReport->id_laporan) }}"
                            class="px-5 py-2.5 bg-[#16a34a] hover:bg-[#15803d] text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-150 active:scale-[0.98]"
                        >
                            Verifikasi Laporan
                        </button>
                    </div>
                </form>

            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-12 text-center text-slate-400">
                <p class="text-sm font-medium">Pilih laporan dari panel sebelah kiri untuk melihat detail verifikasi.</p>
            </div>
        @endif
    </div>

</div>

<script>
    function confirmReject() {
        const catatan = document.getElementById('catatan_verifikasi').value.trim();
        if (!catatan) {
            alert('Harap isi alasan penolakan pada kolom catatan verifikasi sebelum menolak laporan.');
            document.getElementById('catatan_verifikasi').focus();
            return false;
        }
        return confirm('Apakah Anda yakin ingin menolak laporan ini?');
    }
</script>
@endsection
