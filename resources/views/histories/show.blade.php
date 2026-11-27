@extends('layouts.admin')
@use('App\Support\ReportStatusPresenter')

@section('title', 'Riwayat Penanganan ' . $report->nomor_laporan)
@section('heading', 'Riwayat Penanganan')

@section('content')
    {{-- Tombol Navigasi Kembali --}}
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('histories.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-blue-700 transition-colors">
            <span aria-hidden="true">&larr;</span> Kembali ke Daftar Riwayat
        </a>
    </div>

    {{-- Tab Navigasi: Per Laporan vs Per Perangkat --}}
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <a href="{{ route('histories.show', $report) }}"
               class="border-pink-600 text-pink-600 font-semibold whitespace-nowrap py-3 px-1 border-b-2 text-sm">
                Per Laporan
            </a>
            <a href="{{ route('histories.index', ['tab' => 'perangkat', 'cari' => $report->device?->kode_aset]) }}"
               class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium whitespace-nowrap py-3 px-1 border-b-2 text-sm transition-colors">
                Per Perangkat
            </a>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        {{-- ==================== KOLOM KIRI: TIMELINE PENANGANAN ==================== --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-7">
                {{-- Header Laporan --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-6 border-b border-slate-100">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                            {{ $report->nomor_laporan }} &middot; {{ $report->device?->nama_perangkat ?? 'Perangkat' }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Kode Aset: <span class="font-medium text-slate-600">{{ $report->device?->kode_aset ?? '-' }}</span>
                        </p>
                    </div>
                    <span class="inline-flex items-center self-start sm:self-center rounded-full border px-3 py-1 text-xs font-semibold {{ ReportStatusPresenter::badge($report->status?->nama_status) }}">
                        {{ ReportStatusPresenter::label($report->status?->nama_status) }}
                    </span>
                </div>

                {{-- Timeline Perubahan Status --}}
                <div class="mt-8">
                    @if ($report->statusHistories->isEmpty())
                        <div class="py-8 text-center text-slate-500 text-sm">
                            Belum ada riwayat status yang tercatat untuk laporan ini.
                        </div>
                    @else
                        <ol class="relative border-l-2 border-slate-200 ml-4 space-y-8">
                            @foreach ($report->statusHistories as $history)
                                @php
                                    $namaStatus = $history->status?->nama_status;
                                    $label = ReportStatusPresenter::label($namaStatus);
                                    
                                    // Warna titik timeline
                                    $dotColor = match ($namaStatus) {
                                        'Menunggu Verifikasi' => 'bg-slate-400 ring-slate-100',
                                        'Diverifikasi' => 'bg-blue-500 ring-blue-100',
                                        'Ditugaskan' => 'bg-purple-500 ring-purple-100',
                                        'Sedang Dikerjakan' => 'bg-amber-500 ring-amber-100',
                                        'Menunggu Validasi' => 'bg-cyan-500 ring-cyan-100',
                                        'Selesai' => 'bg-emerald-500 ring-emerald-100',
                                        'Ditolak' => 'bg-rose-500 ring-rose-100',
                                        default => 'bg-slate-400 ring-slate-100',
                                    };
                                @endphp
                                <li class="relative pl-6">
                                    {{-- Bullet dot --}}
                                    <span class="absolute -left-[9px] top-1 h-4 w-4 rounded-full border-2 border-white {{ $dotColor }} ring-4"></span>

                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            {{-- Status Badge --}}
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ ReportStatusPresenter::badge($namaStatus) }}">
                                                {{ $label }}
                                            </span>

                                            {{-- Nama Petugas & Peran --}}
                                            <span class="text-sm font-bold text-slate-800">
                                                {{ $history->changer?->nama ?? 'Sistem' }}
                                                @if ($history->changer?->role)
                                                    <span class="font-normal text-slate-500">({{ $history->changer->role->nama_peran }})</span>
                                                @endif
                                            </span>
                                        </div>

                                        {{-- Waktu Perubahan --}}
                                        <span class="text-xs text-slate-400">
                                            {{ ReportStatusPresenter::formatDate($history->waktu_perubahan) }}
                                        </span>
                                    </div>

                                    {{-- Catatan Perubahan --}}
                                    @if ($history->catatan_perubahan)
                                        <p class="mt-2 text-sm text-slate-700 leading-relaxed bg-slate-50/80 rounded-lg p-3 border border-slate-100 whitespace-pre-line">
                                            {{ $history->catatan_perubahan }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach

                            {{-- Tanggapan Pelapor di Akhir Timeline jika ada --}}
                            @foreach ($report->comments as $comment)
                                <li class="relative pl-6">
                                    <span class="absolute -left-[9px] top-1 h-4 w-4 rounded-full border-2 border-white bg-cyan-500 ring-4 ring-cyan-100"></span>

                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-0.5 text-xs font-semibold text-cyan-800">
                                                Tanggapan
                                            </span>

                                            <span class="text-sm font-bold text-slate-800">
                                                {{ $comment->user?->nama ?? 'Pelapor' }}
                                                @if ($comment->user?->role)
                                                    <span class="font-normal text-slate-500">({{ $comment->user->role->nama_peran }})</span>
                                                @endif
                                            </span>
                                        </div>

                                        <span class="text-xs text-slate-400">
                                            {{ ReportStatusPresenter::formatDate($comment->waktu_tanggapan) }}
                                        </span>
                                    </div>

                                    <div class="mt-2 text-sm text-slate-700 leading-relaxed bg-cyan-50/40 rounded-lg p-3 border border-cyan-100/70">
                                        @if ($comment->kesesuaian_label)
                                            <span class="font-semibold text-slate-800 mr-2">{{ $comment->kesesuaian_label }}</span>
                                        @endif
                                        @if ($comment->rating_stars)
                                            <span class="text-amber-500 font-bold mr-2">{{ $comment->rating_stars }}</span>
                                        @endif
                                        <span>&ldquo;{{ $comment->komentar_bersih }}&rdquo;</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        </div>

        {{-- ==================== KOLOM KANAN: RINGKASAN & RIWAYAT PERANGKAT ==================== --}}
        <div class="space-y-6">
            {{-- Kartu Ringkasan Penanganan --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h4 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">
                    Ringkasan Penanganan
                </h4>

                <dl class="space-y-3.5 text-sm">
                    <div class="flex justify-between items-start gap-2">
                        <dt class="text-slate-500 text-xs font-medium">Lokasi</dt>
                        <dd class="font-semibold text-slate-800 text-right">{{ $ringkasan['lokasi'] ?: '-' }}</dd>
                    </div>

                    <div class="flex justify-between items-start gap-2">
                        <dt class="text-slate-500 text-xs font-medium">Kategori</dt>
                        <dd class="font-semibold text-slate-800 text-right">{{ $ringkasan['kategori'] }}</dd>
                    </div>

                    <div class="flex justify-between items-start gap-2">
                        <dt class="text-slate-500 text-xs font-medium">Durasi penanganan</dt>
                        <dd class="font-semibold text-slate-800 text-right">
                            @if ($ringkasan['durasi'])
                                @php
                                    $d = $ringkasan['durasi'];
                                    $durasiStr = [];
                                    if ($d->d > 0) $durasiStr[] = $d->d . ' hari';
                                    if ($d->h > 0) $durasiStr[] = $d->h . ' jam';
                                    if ($d->i > 0 && empty($durasiStr)) $durasiStr[] = $d->i . ' menit';
                                @endphp
                                {{ !empty($durasiStr) ? implode(' ', $durasiStr) : '< 1 jam' }}
                            @else
                                <span class="text-slate-400 font-normal">Sedang berlangsung</span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex justify-between items-start gap-2">
                        <dt class="text-slate-500 text-xs font-medium">Penilaian pelapor</dt>
                        <dd class="font-semibold text-slate-800 text-right">
                            @if ($ringkasan['penilaian'])
                                <span class="text-amber-600 font-bold">{{ $ringkasan['penilaian'] }} / 5</span>
                            @else
                                <span class="text-slate-400 font-normal">Belum ada penilaian</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Kartu Riwayat Perangkat Ini --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                    <h4 class="text-sm font-bold text-slate-900">
                        Riwayat Perangkat Ini
                    </h4>
                </div>

                @if ($riwayatPerangkat['laporan']->isEmpty())
                    <p class="text-xs text-slate-400">Tidak ada laporan sebelumnya pada perangkat ini.</p>
                @else
                    <ul class="divide-y divide-slate-100 text-xs">
                        @foreach ($riwayatPerangkat['laporan'] as $item)
                            <li class="py-2.5 flex items-center justify-between gap-2 {{ $item->id_laporan === $report->id_laporan ? 'bg-pink-50/50 -mx-2 px-2 rounded-lg' : '' }}">
                                <div>
                                    <a href="{{ route('histories.show', $item) }}"
                                       class="font-semibold {{ $item->id_laporan === $report->id_laporan ? 'text-pink-700' : 'text-blue-700 hover:underline' }}">
                                        {{ $item->nomor_laporan }}
                                    </a>
                                    <p class="text-slate-500 text-[11px] mt-0.5 line-clamp-1">
                                        {{ $item->waktu_laporan?->translatedFormat('M Y') }} &middot; {{ Str::limit($item->deskripsi_kerusakan, 30) }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold shrink-0 {{ ReportStatusPresenter::badge($item->status?->nama_status) }}">
                                    {{ ReportStatusPresenter::label($item->status?->nama_status) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 pt-3 border-t border-slate-100 text-center">
                        <p class="text-xs font-medium text-slate-500">
                            Total <span class="font-bold text-slate-800">{{ $riwayatPerangkat['total'] }} kali kerusakan</span> dalam 12 bulan
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
