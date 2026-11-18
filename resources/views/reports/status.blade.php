@extends('layouts.admin')

@section('title', 'Pemantauan Status Laporan')
@section('heading', 'Pemantauan Status Laporan')

@section('content')
    @php
        $activeStatus = (int) ($filters['status'] ?? 0);
        $hasFilter = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    @endphp

    {{-- Ringkasan jumlah laporan per status --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        @foreach ($statuses as $status)
            @php
                $isActive = $activeStatus === (int) $status->id_status;
                $cardQuery = request()->query();
                unset($cardQuery['page']);

                if ($isActive) {
                    unset($cardQuery['status']);
                } else {
                    $cardQuery['status'] = $status->id_status;
                }

                $cardUrl = route('reports.status', $cardQuery);
                $count = (int) ($counts[$status->id_status] ?? 0);
            @endphp

            <a href="{{ $cardUrl }}"
               class="block bg-white rounded-xl shadow-sm border border-slate-200/80 border-t-4 {{ \App\Support\ReportStatusPresenter::cardBorder($status->nama_status) }} px-3.5 py-3 transition hover:-translate-y-0.5 hover:shadow-md {{ $isActive ? 'ring-2 ring-blue-500/60' : '' }}">
                <p class="text-[11px] font-medium leading-tight text-slate-500 min-h-[2.2rem]">
                    {{ \App\Support\ReportStatusPresenter::label($status->nama_status) }}
                </p>
                <p class="mt-0.5 text-xl font-bold text-slate-900">{{ $count }}</p>
                <p class="text-[10px] text-slate-400">laporan</p>
            </a>
        @endforeach
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('reports.status') }}" id="filter-form"
          class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
        @if (filled($filters['status'] ?? null))
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
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

            <div>
                <label for="prioritas" class="block text-xs font-semibold text-slate-600 mb-1.5">Prioritas</label>
                <select id="prioritas" name="prioritas"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Semua Prioritas</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->id_prioritas }}" @selected((string) ($filters['prioritas'] ?? '') === (string) $priority->id_prioritas)>
                            {{ $priority->nama_prioritas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="periode_mulai" class="block text-xs font-semibold text-slate-600 mb-1.5">Periode Mulai</label>
                <input type="date" id="periode_mulai" name="periode_mulai" value="{{ $filters['periode_mulai'] ?? '' }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="periode_selesai" class="block text-xs font-semibold text-slate-600 mb-1.5">Periode Selesai</label>
                <input type="date" id="periode_selesai" name="periode_selesai" value="{{ $filters['periode_selesai'] ?? '' }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="cari" class="block text-xs font-semibold text-slate-600 mb-1.5">Pencarian</label>
                <input type="search" id="cari" name="cari" value="{{ $filters['cari'] ?? '' }}"
                       placeholder="No. laporan / perangkat"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-500">
                Gunakan filter untuk mempersempit daftar. Klik kartu status untuk memfilter berdasarkan status.
            </p>
            <div class="flex items-center gap-2">
                @if ($hasFilter)
                    <a href="{{ route('reports.status') }}"
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

    {{-- Tabel laporan --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[1050px] w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">No. Laporan</th>
                        <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Perangkat</th>
                        <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Lokasi</th>
                        <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Prioritas</th>
                        <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Teknisi</th>
                        <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Diperbarui</th>
                        <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="text-[13px] font-semibold text-blue-700">{{ $report->nomor_laporan }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-[13px] text-slate-700">
                                {{ $report->device?->nama_perangkat ?? '–' }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-[13px] text-slate-600">
                                @if ($report->room)
                                    {{ $report->room->building?->nama_gedung ?? '–' }}, {{ $report->room->nama_ruangan }}
                                @else
                                    –
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-[13px] font-medium {{ \App\Support\ReportStatusPresenter::priorityColor($report->priority?->nama_prioritas) }}">
                                {{ $report->priority?->nama_prioritas ?? '–' }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-medium {{ \App\Support\ReportStatusPresenter::badge($report->status?->nama_status) }}">
                                    {{ \App\Support\ReportStatusPresenter::label($report->status?->nama_status) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-[13px] text-slate-600">
                                {{ $report->latestAssignment?->technician?->short_name ?? '–' }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-[13px] text-slate-500">
                                {{ \App\Support\ReportStatusPresenter::formatDate($report->status_histories_max_waktu_perubahan ?? $report->created_at) }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right space-x-1.5">
                                <a href="{{ route('reports.show', $report) }}"
                                   class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                    Detail
                                </a>
                                @if ($report->status?->nama_status === 'Selesai')
                                    <a href="{{ route('reports.comments.create', $report) }}"
                                       class="inline-flex items-center rounded-lg border border-pink-200 bg-pink-50 px-2.5 py-1.5 text-xs font-semibold text-[#db2777] hover:bg-pink-100">
                                        Tanggapan
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <p class="text-sm font-semibold text-slate-700">Tidak ada laporan sesuai filter</p>
                                <p class="mt-1 text-sm text-slate-500">Coba ubah kombinasi filter atau reset pencarian.</p>
                                <a href="{{ route('reports.status') }}"
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
                        timer = window.setTimeout(() => form.submit(), 350);
                    });
                }
            })();
        </script>
    @endpush
@endsection
