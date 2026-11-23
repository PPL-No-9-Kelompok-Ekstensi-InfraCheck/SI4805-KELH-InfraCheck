@extends('layouts.admin')

@section('title', 'Riwayat Penanganan')
@section('heading', 'Riwayat Penanganan')

@section('content')
    @php
        $isPerangkat = ($tab ?? 'laporan') === 'perangkat';
        $hasFilter = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    @endphp

    {{-- Tab Navigasi: Per Laporan vs Per Perangkat --}}
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <a href="{{ route('histories.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'laporan'])) }}"
               class="{{ ! $isPerangkat ? 'border-pink-600 text-pink-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }} whitespace-nowrap py-3 px-1 border-b-2 text-sm transition-colors">
                Per Laporan
            </a>
            <a href="{{ route('histories.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'perangkat'])) }}"
               class="{{ $isPerangkat ? 'border-pink-600 text-pink-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }} whitespace-nowrap py-3 px-1 border-b-2 text-sm transition-colors">
                Per Perangkat
            </a>
        </nav>
    </div>

    {{-- Filter & Pencarian --}}
    <form method="GET" action="{{ route('histories.index') }}" id="filter-form"
          class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
        <input type="hidden" name="tab" value="{{ $tab ?? 'laporan' }}">

        <div class="grid grid-cols-1 md:grid-cols-3 {{ ! $isPerangkat ? 'xl:grid-cols-5' : 'xl:grid-cols-3' }} gap-3">
            <div>
                <label for="gedung" class="block text-xs font-semibold text-slate-600 mb-1.5">Gedung</label>
                <select id="gedung" name="gedung"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Semua Gedung</option>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id_gedung }}" @selected((string) ($filters['gedung'] ?? '') === (string) $building->id_gedung)>
                            {{ $building->nama_gedung }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="kategori" class="block text-xs font-semibold text-slate-600 mb-1.5">Kategori</label>
                <select id="kategori" name="kategori"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id_kategori }}" @selected((string) ($filters['kategori'] ?? '') === (string) $category->id_kategori)>
                            {{ $category->nama_kategori }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if (! $isPerangkat)
                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-600 mb-1.5">Status Terkini</label>
                    <select id="status" name="status"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->id_status }}" @selected((string) ($filters['status'] ?? '') === (string) $st->id_status)>
                                {{ \App\Support\ReportStatusPresenter::label($st->nama_status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="periode_mulai" class="block text-xs font-semibold text-slate-600 mb-1.5">Periode Mulai</label>
                    <input type="date" id="periode_mulai" name="periode_mulai" value="{{ $filters['periode_mulai'] ?? '' }}"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
            @endif

            <div>
                <label for="cari" class="block text-xs font-semibold text-slate-600 mb-1.5">Pencarian</label>
                <input type="search" id="cari" name="cari" value="{{ $filters['cari'] ?? '' }}"
                       placeholder="{{ $isPerangkat ? 'Nama perangkat / kode aset' : 'No. laporan / nama perangkat' }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-500">
                Menyajikan catatan perubahan status, durasi, dan riwayat penanganan untuk audit dan evaluasi.
            </p>
            <div class="flex items-center gap-2">
                @if ($hasFilter)
                    <a href="{{ route('histories.index', ['tab' => $tab ?? 'laporan']) }}"
                       class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Reset
                    </a>
                @endif
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Terapkan
                </button>
            </div>
        </div>
    </form>

    @if (! $isPerangkat)
        {{-- ==================== TAB: PER LAPORAN ==================== --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[1050px] w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">No. Laporan</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Perangkat</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Lokasi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Pelapor</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Teknisi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Jml Perubahan</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Perubahan Terakhir</th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reports as $report)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-blue-700">{{ $report->nomor_laporan }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-800">
                                    <div class="font-medium">{{ $report->device?->nama_perangkat ?? '–' }}</div>
                                    <div class="text-xs text-slate-400">{{ $report->device?->kode_aset ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
                                    @if ($report->room)
                                        {{ $report->room->building?->nama_gedung ?? '–' }}, {{ $report->room->nama_ruangan }}
                                    @else
                                        –
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ \App\Support\ReportStatusPresenter::badge($report->status?->nama_status) }}">
                                        {{ \App\Support\ReportStatusPresenter::label($report->status?->nama_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-700">
                                    {{ $report->reporter?->nama ?? '–' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
                                    {{ $report->latestAssignment?->technician?->nama ?? '–' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ $report->status_histories_count }} catatan
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500">
                                    {{ \App\Support\ReportStatusPresenter::formatDate($report->status_histories_max_waktu_perubahan ?? $report->created_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <a href="{{ route('histories.show', $report) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-pink-200 bg-pink-50 px-3 py-1.5 text-xs font-semibold text-pink-700 hover:bg-pink-100 transition-colors">
                                        Lihat Riwayat &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-16 text-center">
                                    <p class="text-sm font-semibold text-slate-700">Tidak ada data riwayat penanganan</p>
                                    <p class="mt-1 text-sm text-slate-500">Coba ubah kombinasi filter atau reset pencarian.</p>
                                    <a href="{{ route('histories.index') }}"
                                       class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                        Reset Filter
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">
                    Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan
                </p>
                <div>
                    {{ $reports->links() }}
                </div>
            </div>
        </div>
    @else
        {{-- ==================== TAB: PER PERANGKAT ==================== --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Kode Aset</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Nama Perangkat</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Jenis / Kategori</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Lokasi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Kerusakan 12 Bln</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Kondisi</th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($devices as $device)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-slate-700">
                                    {{ $device->kode_aset }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-slate-900">
                                    {{ $device->nama_perangkat }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
                                    <div>{{ $device->deviceType?->nama_jenis ?? '–' }}</div>
                                    <div class="text-xs text-slate-400">{{ $device->deviceType?->category?->nama_kategori ?? '–' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
                                    @if ($device->room)
                                        {{ $device->room->building?->nama_gedung ?? '–' }}, {{ $device->room->nama_ruangan }}
                                    @else
                                        –
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-700">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $device->reports_count > 2 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $device->reports_count }} kali kerusakan
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @php
                                        $kondisiRaw = $device->getRawOriginal('kondisi');
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                        {{ $kondisiRaw === 'Baik' ? 'bg-emerald-100 text-emerald-800' : ($kondisiRaw === 'Rusak Ringan' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $kondisiRaw ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    @php
                                        $lastReport = $device->reports()->latest('waktu_laporan')->first();
                                    @endphp
                                    @if ($lastReport)
                                        <a href="{{ route('histories.show', $lastReport) }}"
                                           class="inline-flex items-center gap-1 rounded-lg border border-pink-200 bg-pink-50 px-3 py-1.5 text-xs font-semibold text-pink-700 hover:bg-pink-100 transition-colors">
                                            Laporan Terakhir &rarr;
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Belum ada laporan</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <p class="text-sm font-semibold text-slate-700">Tidak ada perangkat yang ditemukan</p>
                                    <p class="mt-1 text-sm text-slate-500">Coba ubah filter atau kata kunci pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">
                    Menampilkan {{ $devices->firstItem() ?? 0 }}–{{ $devices->lastItem() ?? 0 }} dari {{ $devices->total() }} perangkat
                </p>
                <div>
                    {{ $devices->links() }}
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('filter-form');
                if (!form) return;

                form.querySelectorAll('select, input[type="date"]').forEach((element) => {
                    element.addEventListener('change', () => form.submit());
                });

                const search = document.getElementById('cari');
                let timer = null;

                if (search) {
                    search.addEventListener('input', () => {
                        window.clearTimeout(timer);
                        timer = window.setTimeout(() => form.submit(), 400);
                    });
                }
            })();
        </script>
    @endpush
@endsection
