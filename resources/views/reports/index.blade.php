@extends('layouts.app')

@section('judul', 'Status Laporan')

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

    $warnaUrgensi = [
        'Rendah' => 'bg-slate-100 text-slate-600',
        'Sedang' => 'bg-blue-100 text-blue-700',
        'Tinggi' => 'bg-amber-100 text-amber-700',
        'Kritis' => 'bg-red-100 text-red-600',
    ];
@endphp

@section('konten')
<div class="max-w-6xl space-y-6">

    <!-- Ringkasan laporan milik pelapor -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5">
            <p class="text-xs font-semibold text-slate-500">Total Laporan</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $ringkasan['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5">
            <p class="text-xs font-semibold text-slate-500">Sedang Diproses</p>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $ringkasan['aktif'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5">
            <p class="text-xs font-semibold text-slate-500">Selesai</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $ringkasan['selesai'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5">
            <p class="text-xs font-semibold text-slate-500">Ditolak / Dibatalkan</p>
            <p class="mt-1 text-2xl font-bold text-slate-500">{{ $ringkasan['ditolak'] }}</p>
        </div>
    </div>

    <!-- Daftar laporan -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80">
        <div class="p-6 border-b border-slate-200/80 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Laporan Kerusakan Saya</h3>
                <p class="text-xs text-slate-500">
                    Pantau perkembangan laporan yang Anda ajukan. Halaman ini hanya menampilkan laporan milik akun Anda.
                </p>
            </div>

            <!-- Filter status -->
            <form action="{{ route('reports.index') }}" method="GET" class="flex items-center gap-2">
                <label for="status" class="text-xs font-semibold text-slate-600">Status</label>
                <select
                    id="status"
                    name="status"
                    class="px-3 py-2 text-xs text-slate-700 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Semua status</option>
                    @foreach ($daftarStatus as $status)
                        <option value="{{ $status->nama_status }}" @selected($filterStatus === $status->nama_status)>
                            {{ $status->nama_status }}
                        </option>
                    @endforeach
                </select>
                <button
                    type="submit"
                    class="px-3 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                >
                    Terapkan
                </button>
                @if ($filterStatus !== '')
                    <a href="{{ route('reports.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-700">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        @if ($laporan->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-3 text-sm font-semibold text-slate-700">
                    {{ $filterStatus !== '' ? 'Tidak ada laporan dengan status tersebut.' : 'Belum ada laporan kerusakan.' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Ajukan laporan kerusakan terlebih dahulu untuk mulai memantau statusnya.
                </p>
                <a href="{{ route('reports.create') }}" class="inline-flex mt-4 px-4 py-2 text-xs font-semibold text-white bg-[#e11d48] hover:bg-[#be123c] rounded-lg transition-colors">
                    Buat Laporan
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <th class="px-6 py-3 font-semibold">Nomor Laporan</th>
                            <th class="px-4 py-3 font-semibold">Perangkat &amp; Lokasi</th>
                            <th class="px-4 py-3 font-semibold">Urgensi</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold w-32">Progres</th>
                            <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($laporan as $item)
                            @php
                                $namaStatus = $item->status?->nama_status ?? 'Tidak Diketahui';
                                $warna = $warnaStatus[$namaStatus] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                $persentase = $item->persentase_progres;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="{{ route('reports.show', $item->id_laporan) }}" class="font-semibold text-blue-700 hover:text-blue-800">
                                        {{ $item->nomor_laporan }}
                                    </a>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $item->waktu_laporan?->translatedFormat('d M Y, H:i') ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-slate-800">{{ $item->device?->nama_perangkat ?? 'Perangkat terhapus' }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $item->device?->kode_aset ?? '-' }}</p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ $item->device?->room?->building?->nama_gedung ?? 'Tanpa Gedung' }}
                                        &middot;
                                        {{ $item->device?->room?->nama_ruangan ?? 'Tanpa Ruangan' }}
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $warnaUrgensi[$item->urgency?->nama_urgensi] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $item->urgency?->nama_urgensi ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $warna }}">
                                        {{ $namaStatus }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-16 bg-slate-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ $persentase }}%"></div>
                                        </div>
                                        <span class="text-[11px] font-semibold text-slate-600">{{ $persentase }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <a href="{{ route('reports.show', $item->id_laporan) }}" class="text-xs font-semibold text-blue-700 hover:text-blue-800 whitespace-nowrap">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-200/80">
                {{ $laporan->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
