@extends('layouts.app')

@section('title', 'Detail Perangkat')

@section('content')
    <div class="mb-5">
        <a href="{{ route('devices.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-blue-700">
            <span aria-hidden="true">&larr;</span> Kembali ke Data Perangkat
        </a>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Detail Perangkat</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $device->nama_perangkat }}</h2>
            <p class="mt-1 font-mono text-sm text-slate-500">{{ $device->kode_aset }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $device->kondisi?->badgeClass() ?? 'bg-slate-100 text-slate-600' }}">
                Kondisi: {{ $device->kondisi?->label() ?? '-' }}
            </span>
            <a href="{{ route('devices.edit', $device) }}"
               class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm transition-colors">
                Edit Perangkat
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Informasi perangkat --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Informasi Perangkat</h3>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400">Kode Inventaris</dt>
                        <dd class="mt-0.5 font-mono font-medium text-slate-800">{{ $device->kode_aset }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Nama Perangkat</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $device->nama_perangkat }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Jenis Perangkat</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $device->jenis_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Kategori Infrastruktur</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $device->kategori_label ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Ruangan</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $device->lokasi_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Gedung</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $device->gedung_label ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Kondisi</dt>
                        <dd class="mt-0.5">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $device->kondisi?->badgeClass() ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $device->kondisi?->label() ?? '-' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Riwayat laporan kerusakan perangkat --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Laporan Kerusakan Terkait</h3>
                    <p class="mt-1 text-xs text-slate-500">Laporan kerusakan yang pernah diajukan untuk perangkat ini.</p>
                </div>

                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nomor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Pelapor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($reports as $report)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-600">{{ $report->nomor_laporan }}</td>
                                <td class="px-6 py-3.5 text-slate-600">
                                    {{ $report->waktu_laporan?->translatedFormat('d M Y, H:i') ?? '-' }}
                                </td>
                                <td class="px-6 py-3.5 text-slate-600">{{ $report->status?->nama_status ?? '-' }}</td>
                                <td class="px-6 py-3.5 text-slate-600">{{ $report->reporter?->nama ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500 text-sm">
                                    Belum ada laporan kerusakan untuk perangkat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Ringkasan</h3>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400">Total Laporan</dt>
                        <dd class="mt-0.5 text-2xl font-bold text-slate-900">{{ $reports->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Laporan Aktif</dt>
                        <dd class="mt-0.5 text-2xl font-bold text-slate-900">
                            {{ $reports->reject(fn ($report) => in_array($report->status?->nama_status, ['Selesai', 'Ditolak', 'Dibatalkan'], true))->count() }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
