@extends('layouts.app')

@section('title', 'Dokumentasi Hasil Perbaikan')
@section('page_title', 'Dokumentasi Perbaikan')

@section('content')
    @php
        $isTeknisi = auth()->user()->hasRole('Teknisi');
    @endphp

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h3 class="text-base font-bold text-slate-900">Dokumentasi Hasil Perbaikan</h3>
            <p class="text-xs text-slate-500 mt-1">
                @if ($isTeknisi)
                    Unggah bukti foto sebelum dan sesudah perbaikan, tindakan yang dilakukan, serta suku cadang yang diganti.
                @else
                    Pantau dokumentasi dan bukti fisik hasil perbaikan perangkat infrastruktur teknologi.
                @endif
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 self-start px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-600">
            {{ $assignments->count() }} penugasan
        </span>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
        @if ($assignments->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-3xl mb-3">📷</p>
                <p class="text-sm font-semibold text-slate-700">Belum ada penugasan untuk didokumentasikan</p>
                <p class="text-xs text-slate-500 mt-1">
                    @if ($isTeknisi)
                        Penugasan perbaikan yang ditetapkan untuk Anda akan muncul di sini.
                    @else
                        Belum ada penugasan perbaikan yang tersedia.
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100 bg-slate-50/60">
                            <th class="px-6 py-3 font-semibold">NO. LAPORAN</th>
                            <th class="px-4 py-3 font-semibold">PERANGKAT</th>
                            <th class="px-4 py-3 font-semibold">LOKASI</th>
                            @unless ($isTeknisi)
                                <th class="px-4 py-3 font-semibold">TEKNISI</th>
                            @endunless
                            <th class="px-4 py-3 font-semibold">STATUS LAPORAN</th>
                            <th class="px-4 py-3 font-semibold">FOTO DOKUMENTASI</th>
                            <th class="px-6 py-3 font-semibold text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($assignments as $assignment)
                            @php
                                $report = $assignment->report;
                                $docsCount = $assignment->progressUpdates->flatMap->documents->count();
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-900 whitespace-nowrap">
                                    {{ $report?->nomor_laporan ?? '-' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-800">{{ $report?->device?->nama_perangkat ?? '-' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $report?->device?->kode_aset }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div>{{ $report?->room?->nama_ruangan ?? '-' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $report?->room?->building?->nama_gedung }}</div>
                                </td>
                                @unless ($isTeknisi)
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-800">{{ $assignment->technician?->nama ?? '-' }}</div>
                                    </td>
                                @endunless
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <x-status-badge :status="$report?->status?->nama_status" />
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if ($docsCount > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            📷 {{ $docsCount }} berkas
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs italic">Belum ada foto</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('documents.create', $assignment) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-colors shadow-sm">
                                        {{ $isTeknisi ? 'Kelola Dokumentasi' : 'Lihat Dokumentasi' }} &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
