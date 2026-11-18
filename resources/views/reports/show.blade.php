@extends('layouts.app')

@section('judul', 'Detail Laporan')

@php
    // Warna badge untuk setiap status laporan
    $warnaStatus = [
        'Menunggu Verifikasi' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Diverifikasi' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Ditolak' => 'bg-red-50 text-red-600 border-red-200',
        'Ditugaskan' => 'bg-purple-50 text-purple-700 border-purple-200',
        'Sedang Dikerjakan' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Menunggu Validasi' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'Selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Dibatalkan' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];

    $namaStatus = $laporan->status?->nama_status ?? 'Tidak Diketahui';
    $warna = $warnaStatus[$namaStatus] ?? 'bg-slate-100 text-slate-600 border-slate-200';

    $warnaProgres = match (true) {
        $namaStatus === 'Ditolak' => 'bg-red-500',
        $namaStatus === 'Dibatalkan' => 'bg-slate-400',
        $namaStatus === 'Selesai' => 'bg-emerald-500',
        default => 'bg-blue-500',
    };

    $warnaUrgensi = [
        'Rendah' => 'bg-slate-100 text-slate-600 border-slate-200',
        'Sedang' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Tinggi' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Kritis' => 'bg-red-50 text-red-600 border-red-200',
    ];
@endphp

@section('konten')
<div class="max-w-6xl space-y-6">

    <!-- Kepala halaman -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Status Laporan
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h3 class="text-xl font-bold text-slate-900">{{ $laporan->nomor_laporan }}</h3>
                <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $warna }}">
                    {{ $namaStatus }}
                </span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Diajukan {{ $laporan->waktu_laporan?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
            </p>
        </div>

        <a href="{{ route('reports.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold text-white bg-[#e11d48] hover:bg-[#be123c] rounded-lg shadow-sm transition-colors">
            Buat Laporan Baru
        </a>
    </div>

    <!-- Indikator progres -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Progres Penanganan</h3>
                <p class="text-xs text-slate-500 mt-0.5">Tahapan pemantauan laporan kerusakan Anda.</p>
            </div>
            <span class="text-2xl font-bold {{ $namaStatus === 'Ditolak' ? 'text-red-600' : ($namaStatus === 'Selesai' ? 'text-emerald-600' : 'text-blue-700') }}">
                {{ $progres['persentase'] }}%
            </span>
        </div>

        <div class="mt-3 h-2 w-full bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full {{ $warnaProgres }} rounded-full transition-all duration-500" style="width: {{ $progres['persentase'] }}%"></div>
        </div>

        @if ($progres['statusPenutup'])
            <div class="mt-5 p-4 rounded-lg border text-sm font-medium {{ $namaStatus === 'Ditolak' ? 'bg-red-50 border-red-200 text-red-600' : 'bg-slate-100 border-slate-200 text-slate-600' }}">
                Laporan ini {{ $namaStatus === 'Ditolak' ? 'ditolak oleh Admin Unit IT' : 'dibatalkan' }}.
                Silakan periksa riwayat status di bawah untuk melihat catatan yang diberikan.
            </div>
        @else
            <!-- Stepper tahapan -->
            <ol class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($progres['tahapan'] as $tahap)
                    <li class="relative">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-[11px] font-bold border-2
                                {{ $tahap['tercapai'] ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-300 text-slate-400' }}
                                {{ $tahap['aktif'] ? 'ring-4 ring-blue-100' : '' }}">
                                @if ($tahap['tercapai'])
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <span class="text-[11px] font-semibold leading-tight {{ $tahap['aktif'] ? 'text-blue-700' : ($tahap['tercapai'] ? 'text-slate-700' : 'text-slate-400') }}">
                                {{ $tahap['nama'] }}
                            </span>
                        </div>
                        <p class="mt-1.5 pl-9 text-[10px] {{ $tahap['tercapai'] ? 'text-slate-500' : 'text-slate-400' }}">
                            {{ $tahap['waktu']?->translatedFormat('d M Y, H:i') ?? ($tahap['aktif'] ? 'Sedang berlangsung' : 'Belum tercapai') }}
                        </p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- ==================== DETAIL LAPORAN ==================== -->
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Detail Laporan</h3>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Perangkat</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->device?->nama_perangkat ?? 'Perangkat terhapus' }}</dd>
                        <dd class="text-xs text-slate-500">{{ $laporan->device?->kode_aset ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Jenis Perangkat</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->device?->deviceType?->nama_jenis ?? 'Umum' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Lokasi</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->device?->room?->building?->nama_gedung ?? 'Tanpa Gedung' }}</dd>
                        <dd class="text-xs text-slate-500">{{ $laporan->device?->room?->nama_ruangan ?? 'Tanpa Ruangan' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Tingkat Urgensi</dt>
                        <dd class="mt-1">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $warnaUrgensi[$laporan->urgency?->nama_urgensi] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                {{ $laporan->urgency?->nama_urgensi ?? '-' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Prioritas</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->priority?->nama_prioritas ?? 'Belum ditentukan' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Pelapor</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->reporter?->nama ?? '-' }}</dd>
                        <dd class="text-xs text-slate-500">{{ $laporan->reporter?->unit ?? '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 pt-5 border-t border-slate-100">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Deskripsi Kerusakan</p>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $laporan->deskripsi_kerusakan }}</p>
                </div>

                <div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Diverifikasi Oleh</p>
                        <p class="mt-0.5 font-medium text-slate-800">{{ $laporan->verifier?->nama ?? 'Belum diverifikasi' }}</p>
                        @if ($laporan->waktu_verifikasi)
                            <p class="text-xs text-slate-500">{{ $laporan->waktu_verifikasi->translatedFormat('d M Y, H:i') }} WIB</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Catatan Verifikasi</p>
                        <p class="mt-0.5 text-slate-700">{{ $laporan->catatan_verifikasi ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Foto dokumentasi -->
            @if ($laporan->photos->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                    <h3 class="text-sm font-bold text-slate-900 mb-4">Foto Dokumentasi Kerusakan</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($laporan->photos as $foto)
                            <a href="{{ asset($foto->lokasi_berkas) }}" target="_blank" rel="noopener" class="group">
                                <img
                                    src="{{ asset($foto->lokasi_berkas) }}"
                                    alt="Foto dokumentasi kerusakan"
                                    class="h-28 w-full object-cover rounded-lg border border-slate-200 group-hover:opacity-90 transition-opacity"
                                >
                                <span class="mt-1 block text-[10px] text-slate-400">
                                    Diunggah {{ $foto->waktu_unggah?->translatedFormat('d M Y, H:i') ?? '-' }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Informasi penanganan teknisi (bila sudah ada penugasan) -->
            @if ($penugasan)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                    <h3 class="text-sm font-bold text-slate-900 mb-4">Penanganan Teknisi</h3>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Teknisi</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $penugasan->technician?->nama ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Status Penugasan</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 capitalize">{{ $penugasan->status_penugasan }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Waktu Penugasan</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $penugasan->waktu_penugasan?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                        </div>
                        @if ($penugasan->schedule)
                            <div>
                                <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Jadwal Perbaikan</dt>
                                <dd class="mt-0.5 font-medium text-slate-800">
                                    {{ $penugasan->schedule->waktu_mulai?->translatedFormat('d M Y, H:i') ?? '-' }}
                                    &ndash;
                                    {{ $penugasan->schedule->target_selesai?->translatedFormat('d M Y, H:i') ?? '-' }}
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if ($penugasan->progressUpdates->isNotEmpty())
                        <div class="mt-5 pt-5 border-t border-slate-100">
                            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-3">Riwayat Progres Perbaikan</p>
                            <ul class="space-y-3">
                                @foreach ($penugasan->progressUpdates as $progresItem)
                                    <li class="flex gap-3">
                                        <span class="w-10 shrink-0 text-xs font-bold text-blue-700">{{ $progresItem->persentase }}%</span>
                                        <div class="min-w-0">
                                            <p class="text-xs text-slate-700">{{ $progresItem->catatan_progres }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $progresItem->waktu_progres?->translatedFormat('d M Y, H:i') ?? '-' }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- ==================== SIDEBAR: RIWAYAT STATUS ==================== -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-1">Riwayat Status</h3>
                <p class="text-xs text-slate-500 mb-4">Catatan perubahan status laporan Anda.</p>

                <ol class="relative border-l border-slate-200 ml-2 space-y-5">
                    @foreach ($progres['riwayat'] as $riwayat)
                        @php
                            $statusRiwayat = $riwayat->status?->nama_status ?? 'Tidak Diketahui';
                            $warnaRiwayat = $warnaStatus[$statusRiwayat] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                        @endphp
                        <li class="ml-4">
                            <span class="absolute -left-[5px] mt-1.5 w-2.5 h-2.5 rounded-full bg-blue-500 ring-4 ring-white"></span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $warnaRiwayat }}">
                                {{ $statusRiwayat }}
                            </span>
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                {{ $riwayat->waktu_perubahan?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                            </p>
                            @if ($riwayat->changer)
                                <p class="text-[11px] text-slate-500">oleh {{ $riwayat->changer->nama }}</p>
                            @endif
                            @if ($riwayat->catatan_perubahan)
                                <p class="mt-1 text-xs text-slate-700 leading-relaxed">{{ $riwayat->catatan_perubahan }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Butuh Bantuan?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Laporan Anda akan diverifikasi oleh Admin Unit IT. Jika ada informasi tambahan,
                    silakan hubungi Unit IT dengan menyebutkan nomor laporan
                    <strong>{{ $laporan->nomor_laporan }}</strong>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
