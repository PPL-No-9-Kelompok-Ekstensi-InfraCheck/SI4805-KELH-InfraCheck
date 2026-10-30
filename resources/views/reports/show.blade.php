@extends('layouts.admin')

@section('title', 'Detail Laporan ' . $report->nomor_laporan)
@section('heading', 'Detail Laporan')

@section('content')
    @php
        use App\Support\ReportStatusPresenter;
    @endphp

    <div class="mb-5">
        <a href="{{ route('reports.status') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-blue-700">
            <span aria-hidden="true">&larr;</span> Kembali ke Pemantauan Status
        </a>
    </div>

    {{-- Ringkasan laporan --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Laporan</p>
                <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ $report->nomor_laporan }}</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Dilaporkan {{ ReportStatusPresenter::formatDate($report->waktu_laporan) }} oleh
                    {{ $report->reporter?->nama ?? '–' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ ReportStatusPresenter::badge($report->status?->nama_status) }}">
                    {{ ReportStatusPresenter::label($report->status?->nama_status) }}
                </span>
                @if ($report->priority)
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold {{ ReportStatusPresenter::priorityColor($report->priority->nama_prioritas) }}">
                        Prioritas: {{ $report->priority->nama_prioritas }}
                    </span>
                @endif
                @if ($report->urgency)
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                        Urgensi: {{ $report->urgency->nama_urgensi }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Kolom kiri: informasi laporan --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Informasi Laporan</h4>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400">Perangkat</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $report->device?->nama_perangkat ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Jenis Perangkat</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $report->device?->deviceType?->nama_jenis ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Lokasi</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            @if ($report->room)
                                {{ $report->room->building?->nama_gedung ?? '–' }}, {{ $report->room->nama_ruangan }}
                            @else
                                –
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Pelapor</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $report->reporter?->nama ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Waktu Verifikasi</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ ReportStatusPresenter::formatDate($report->waktu_verifikasi) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Verifikator</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $report->verifier?->nama ?? '–' }}</dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <p class="text-slate-400 text-sm">Deskripsi Kerusakan</p>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ $report->deskripsi_kerusakan }}</p>
                </div>

                @if ($report->catatan_verifikasi)
                    <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Catatan Verifikasi</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ $report->catatan_verifikasi }}</p>
                    </div>
                @endif
            </div>

            {{-- Foto laporan --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Foto Laporan</h4>

                @if ($report->photos->isEmpty())
                    <p class="text-sm text-slate-500">Tidak ada foto yang dilampirkan.</p>
                @else
                    <ul class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($report->photos as $photo)
                            <li class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 break-all">
                                {{ $photo->lokasi_berkas }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Tanggapan --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Tanggapan</h4>

                @if ($report->comments->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada tanggapan pada laporan ini.</p>
                @else
                    <ul class="space-y-4">
                        @foreach ($report->comments as $comment)
                            <li class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ $comment->user?->nama ?? '–' }}</p>
                                    <p class="text-xs text-slate-400">{{ ReportStatusPresenter::formatDate($comment->waktu_tanggapan) }}</p>
                                </div>
                                <p class="mt-1.5 text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ $comment->isi_tanggapan }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Kolom kanan: penugasan, progres, riwayat status --}}
        <div class="space-y-6">
            {{-- Penugasan --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Penugasan Teknisi</h4>

                @if ($report->assignments->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada penugasan teknisi.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($report->assignments->sortByDesc('waktu_penugasan') as $assignment)
                            <li class="rounded-lg border border-slate-200 p-3.5">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ $assignment->technician?->nama ?? '–' }}</p>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium {{ $assignment->status_penugasan === 'aktif' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                                        {{ ucfirst($assignment->status_penugasan) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-400">{{ ReportStatusPresenter::formatDate($assignment->waktu_penugasan) }}</p>

                                @php
                                    $latestProgress = $assignment->progressUpdates->first();
                                @endphp

                                @if ($latestProgress)
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-xs text-slate-500">
                                            <span>Progres terakhir</span>
                                            <span class="font-semibold text-slate-700">{{ $latestProgress->persentase }}%</span>
                                        </div>
                                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, max(0, $latestProgress->persentase)) }}%"></div>
                                        </div>
                                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ $latestProgress->catatan_progres }}</p>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Riwayat status --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Riwayat Status</h4>

                @if ($report->statusHistories->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada riwayat status.</p>
                @else
                    <ol class="relative space-y-5 border-l border-slate-200 pl-5">
                        @foreach ($report->statusHistories as $history)
                            <li class="relative">
                                <span class="absolute -left-[26px] top-1 h-3 w-3 rounded-full border-2 border-white bg-blue-500"></span>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ ReportStatusPresenter::label($history->status?->nama_status) }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ ReportStatusPresenter::formatDate($history->waktu_perubahan) }}
                                    &middot; {{ $history->changer?->nama ?? 'Sistem' }}
                                </p>
                                @if ($history->catatan_perubahan)
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600 whitespace-pre-line">{{ $history->catatan_perubahan }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </div>
@endsection
