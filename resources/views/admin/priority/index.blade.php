@extends('layouts.admin')

@section('title', 'Penentuan Prioritas Penanganan — InfraCheck')
@section('page_title', 'Penentuan Prioritas Penanganan')

@section('content')
@php
    $deskripsiPrioritas = [
        'Mendesak' => 'Layanan utama kampus lumpuh total atau berdampak luas lintas gedung. Target ditangani segera.',
        'Tinggi' => 'Mengganggu kegiatan belajar banyak orang atau layanan utama (jaringan, lab, ruang ujian). Target ditangani ≤ 1 hari.',
        'Sedang' => 'Mengganggu satu ruangan/kegiatan, masih ada alternatif. Target ≤ 3 hari.',
        'Rendah' => 'Tidak mengganggu kegiatan utama. Target ≤ 7 hari.',
    ];

    $badgePrioritas = [
        'Mendesak' => 'bg-rose-100 text-rose-700 border-rose-200',
        'Tinggi' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Sedang' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Rendah' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">

    <!-- ==================== KOLOM KIRI: DAFTAR LAPORAN TERVERIFIKASI ==================== -->
    <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900">
                    Laporan Terverifikasi Tanpa Prioritas ({{ $withoutPriorityCount }})
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pilih laporan untuk menetapkan atau memperbarui tingkat prioritas penanganan.
                </p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                Total terverifikasi: {{ $verifiedReports->count() }}
            </span>
        </div>

        @if ($verifiedReports->isEmpty())
            <div class="py-12 text-center text-slate-400 text-xs">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Belum ada laporan terverifikasi yang perlu ditetapkan prioritasnya.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                            <th class="pb-3 pr-3 font-semibold">NO. LAPORAN</th>
                            <th class="pb-3 px-3 font-semibold">PERANGKAT</th>
                            <th class="pb-3 px-3 font-semibold">LOKASI</th>
                            <th class="pb-3 px-3 font-semibold">PRIORITAS</th>
                            <th class="pb-3 pl-3 text-right font-semibold">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($verifiedReports as $report)
                            @php
                                $isActive = $selectedReport && $selectedReport->id_laporan === $report->id_laporan;
                                $namaPrioritas = $report->priority?->nama_prioritas;
                                $badgeClass = $namaPrioritas
                                    ? ($badgePrioritas[$namaPrioritas] ?? 'bg-slate-100 text-slate-600 border-slate-200')
                                    : 'bg-rose-50 text-rose-600 border-rose-200';
                            @endphp
                            <tr class="transition-colors {{ $isActive ? 'bg-[#fff1f2]/70' : 'hover:bg-slate-50/70' }}">
                                <td class="py-3.5 pr-3 font-bold {{ $isActive ? 'text-[#be123c]' : 'text-slate-900' }} whitespace-nowrap">
                                    {{ $report->nomor_laporan }}
                                </td>
                                <td class="py-3.5 px-3 text-slate-800 font-medium">
                                    {{ $report->device->nama_perangkat ?? 'Perangkat' }}
                                </td>
                                <td class="py-3.5 px-3 text-slate-600">
                                    {{ $report->room->nama_ruangan ?? '-' }}
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClass }}">
                                        {{ $namaPrioritas ?? 'Belum ditetapkan' }}
                                    </span>
                                </td>
                                <td class="py-3.5 pl-3 text-right whitespace-nowrap">
                                    <a
                                        href="{{ route('admin.priority.index', ['selected' => $report->id_laporan]) }}"
                                        class="inline-flex items-center font-bold {{ $isActive ? 'text-[#e11d48]' : 'text-blue-600 hover:text-blue-800' }}"
                                    >
                                        {{ $isActive ? 'Dipilih' : 'Pilih' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- ==================== KOLOM KANAN: FORMULIR PENENTUAN PRIORITAS ==================== -->
    <div class="lg:col-span-5">
        @if ($selectedReport)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
                <div class="pb-4 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        Prioritas untuk {{ $selectedReport->nomor_laporan }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $selectedReport->device->nama_perangkat ?? 'Perangkat' }} · {{ $selectedReport->room->nama_ruangan ?? '-' }}
                    </p>
                </div>

                <!-- Ringkasan Urgensi Pelapor & Deskripsi -->
                <div class="py-4 border-b border-slate-100 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Urgensi menurut Pelapor</span>
                        <span class="font-bold text-slate-800">{{ $selectedReport->urgency->nama_urgensi ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Status saat ini</span>
                        <span class="font-semibold text-blue-700">{{ $selectedReport->status->nama_status ?? '-' }}</span>
                    </div>
                    <div class="pt-1">
                        <p class="text-slate-400 uppercase tracking-wider font-semibold text-[10px] mb-1">Deskripsi Kerusakan</p>
                        <p class="text-slate-700 leading-relaxed">{{ $selectedReport->deskripsi_kerusakan }}</p>
                    </div>
                </div>

                <form action="{{ route('admin.priority.update', $selectedReport->id_laporan) }}" method="POST" class="pt-5 space-y-5">
                    @csrf

                    <!-- Pilihan Radio Prioritas -->
                    <div class="space-y-3">
                        @foreach ($priorities as $priority)
                            @php
                                $selectedPriorityId = old('id_prioritas', $selectedReport->id_prioritas);
                                $isChecked = (int) $selectedPriorityId === (int) $priority->id_prioritas;
                            @endphp
                            <label
                                class="priority-card flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all {{ $isChecked ? 'border-[#e11d48] bg-[#fff1f2]/50 ring-1 ring-[#e11d48]' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60' }}"
                            >
                                <input
                                    type="radio"
                                    name="id_prioritas"
                                    value="{{ $priority->id_prioritas }}"
                                    class="priority-radio mt-0.5 w-4 h-4 text-[#e11d48] border-slate-300 focus:ring-[#e11d48]"
                                    {{ $isChecked ? 'checked' : '' }}
                                    required
                                >
                                <div class="text-xs">
                                    <p class="font-bold text-slate-900">{{ $priority->nama_prioritas }}</p>
                                    <p class="text-slate-500 mt-0.5 leading-relaxed">
                                        {{ $deskripsiPrioritas[$priority->nama_prioritas] ?? 'Tetapkan prioritas sesuai dampak kerusakan.' }}
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <!-- Alasan Penentuan Prioritas -->
                    <div>
                        <label for="alasan_prioritas" class="block text-xs font-semibold text-slate-600 mb-1.5">
                            Alasan penentuan prioritas
                        </label>
                        <textarea
                            id="alasan_prioritas"
                            name="alasan_prioritas"
                            rows="3"
                            placeholder="Contoh: Wi-Fi satu lantai mati, dipakai 6 ruang kelas."
                            class="w-full px-3.5 py-2.5 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#e11d48]/20 focus:border-[#e11d48] transition-all placeholder:text-slate-400"
                        >{{ old('alasan_prioritas') }}</textarea>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex items-center justify-end gap-3 pt-1">
                        <a
                            href="{{ route('admin.priority.index') }}"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </a>
                        <button
                            type="submit"
                            class="px-5 py-2.5 bg-[#e11d48] hover:bg-[#be123c] text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-150 active:scale-[0.98]"
                        >
                            Simpan Prioritas
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-10 text-center text-slate-400">
                <p class="text-sm font-medium">Pilih laporan terverifikasi di sebelah kiri untuk menentukan prioritas penanganan.</p>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.priority-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.priority-card').forEach(function (card) {
                card.classList.remove('border-[#e11d48]', 'bg-[#fff1f2]/50', 'ring-1', 'ring-[#e11d48]');
                card.classList.add('border-slate-200');
            });
            const activeCard = this.closest('.priority-card');
            if (activeCard) {
                activeCard.classList.remove('border-slate-200');
                activeCard.classList.add('border-[#e11d48]', 'bg-[#fff1f2]/50', 'ring-1', 'ring-[#e11d48]');
            }
        });
    });
</script>
@endpush
