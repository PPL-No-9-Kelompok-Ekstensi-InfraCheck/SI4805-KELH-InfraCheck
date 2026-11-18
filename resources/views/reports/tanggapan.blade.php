@extends('layouts.admin')

@section('title', 'Beri Tanggapan ' . $report->nomor_laporan)
@section('heading', 'Beri Tanggapan atas Penanganan')

@section('content')
    @php
        use App\Support\ReportStatusPresenter;

        $latestAssignment = $report->latestAssignment;
        $technician = $latestAssignment?->technician;
        $latestProgress = $latestAssignment?->progressUpdates?->first();

        $selectedKesesuaian = old('kesesuaian', $existingComment?->parsed_data['kesesuaian'] ?? 'sudah_sesuai');
        $selectedRating = (int) old('rating', $existingComment?->rating ?? 5);
        $komentarText = old('komentar', $existingComment?->komentar_bersih ?? '');
    @endphp

    {{-- Breadcrumb navigasi --}}
    <nav class="mb-5 flex items-center gap-2 text-xs font-medium text-slate-500">
        <a href="{{ route('dashboard') }}" class="hover:text-blue-700">Laporan Saya</a>
        <span class="text-slate-300">&rsaquo;</span>
        <span class="text-slate-600">{{ $report->nomor_laporan }}</span>
        <span class="text-slate-300">&rsaquo;</span>
        <span class="text-slate-800 font-semibold">Tanggapan</span>
    </nav>

    {{-- Notifikasi Sukses / Error --}}
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-800 shadow-sm flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800 shadow-sm">
            <p class="font-semibold mb-1">Periksa kembali isian formulir tanggapan Anda:</p>
            <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($existingComment)
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50/80 p-4 text-xs text-blue-800 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="font-semibold text-blue-900">Anda telah mengirimkan tanggapan untuk laporan ini pada {{ ReportStatusPresenter::formatDate($existingComment->waktu_tanggapan) }}.</p>
                <p class="mt-0.5 text-blue-700">Anda dapat memperbarui isian tanggapan di bawah atau menghapusnya jika diperlukan.</p>
            </div>
            <form action="{{ route('reports.comments.destroy', [$report, $existingComment]) }}"
                  method="POST"
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus tanggapan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 transition-colors">
                    Hapus Tanggapan
                </button>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        {{-- ==================== KOLOM KIRI: RINGKASAN LAPORAN ==================== --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-7">
            <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-5">Ringkasan Laporan</h3>

            <dl class="space-y-3.5 text-sm">
                <div class="flex flex-col sm:flex-row sm:justify-between py-1 border-b border-slate-100">
                    <dt class="text-slate-400 font-medium">No. laporan</dt>
                    <dd class="font-semibold text-slate-900 sm:text-right">{{ $report->nomor_laporan }}</dd>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-between py-1 border-b border-slate-100">
                    <dt class="text-slate-400 font-medium">Perangkat</dt>
                    <dd class="font-semibold text-slate-800 sm:text-right">{{ $report->device?->nama_perangkat ?? '–' }}</dd>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-between py-1 border-b border-slate-100">
                    <dt class="text-slate-400 font-medium">Lokasi</dt>
                    <dd class="font-medium text-slate-700 sm:text-right">
                        @if ($report->room)
                            {{ $report->room->building?->nama_gedung ?? '–' }}, {{ $report->room->nama_ruangan }}
                        @else
                            –
                        @endif
                    </dd>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-between py-1 border-b border-slate-100">
                    <dt class="text-slate-400 font-medium">Keluhan</dt>
                    <dd class="text-slate-700 sm:text-right sm:max-w-xs leading-relaxed whitespace-pre-line">{{ $report->deskripsi_kerusakan }}</dd>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-between py-1 border-b border-slate-100">
                    <dt class="text-slate-400 font-medium">Teknisi</dt>
                    <dd class="font-semibold text-slate-800 sm:text-right">
                        {{ $technician?->nama ?? $technician?->short_name ?? 'Dewi P.' }}
                    </dd>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-between py-1 border-b border-slate-100 items-start sm:items-center">
                    <dt class="text-slate-400 font-medium">Status</dt>
                    <dd class="sm:text-right">
                        <span class="inline-flex items-center rounded-full border px-3 py-0.5 text-xs font-semibold {{ ReportStatusPresenter::badge($report->status?->nama_status) }}">
                            {{ ReportStatusPresenter::label($report->status?->nama_status) }}
                        </span>
                    </dd>
                </div>

                <div class="pt-1">
                    <dt class="text-slate-400 font-medium mb-1">Catatan teknisi</dt>
                    <dd class="text-slate-700 leading-relaxed text-sm bg-slate-50 rounded-lg p-3 border border-slate-100">
                        {{ $latestProgress?->catatan_progres ?? 'Roller pengumpan kertas dibersihkan dan diganti.' }}
                    </dd>
                </div>
            </dl>

            {{-- Dokumentasi hasil perbaikan --}}
            <div class="mt-7 pt-5 border-t border-slate-200/80">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Dokumentasi hasil perbaikan</h4>
                <div class="grid grid-cols-2 gap-3.5">
                    <div class="h-32 rounded-lg bg-slate-100 border border-slate-200 flex flex-col items-center justify-center text-slate-400 text-xs text-center p-3">
                        <svg class="w-7 h-7 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>foto sebelum</span>
                    </div>
                    <div class="h-32 rounded-lg bg-slate-100 border border-slate-200 flex flex-col items-center justify-center text-slate-400 text-xs text-center p-3">
                        <svg class="w-7 h-7 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>foto sesudah</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== KOLOM KANAN: TANGGAPAN ANDA ==================== --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-7">
            <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-5">Tanggapan Anda</h3>

            <form action="{{ $existingComment ? route('reports.comments.update', [$report, $existingComment]) : route('reports.comments.store', $report) }}"
                  method="POST"
                  id="feedbackForm">
                @csrf
                @if ($existingComment)
                    @method('PUT')
                @endif

                {{-- Pertanyaan 1: Apakah perbaikan sudah sesuai? --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-800 mb-2.5">
                        Apakah perbaikan sudah sesuai? <span class="text-rose-500">*</span>
                    </label>

                    <div class="space-y-3">
                        {{-- Opsi: Sudah sesuai --}}
                        <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all option-card {{ $selectedKesesuaian === 'sudah_sesuai' ? 'border-[#db2777] bg-pink-50/40 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white' }}"
                               id="label-sudah-sesuai">
                            <div class="flex items-center h-5">
                                <input type="radio"
                                       name="kesesuaian"
                                       value="sudah_sesuai"
                                       class="h-4 w-4 text-[#db2777] border-slate-300 focus:ring-[#db2777]"
                                       {{ $selectedKesesuaian === 'sudah_sesuai' ? 'checked' : '' }}
                                       onchange="updateOptionCards()">
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-semibold text-slate-900 block">Sudah sesuai</span>
                                <span class="text-xs text-slate-500 mt-0.5 block">Laporan akan ditutup.</span>
                            </div>
                        </label>

                        {{-- Opsi: Belum sesuai --}}
                        <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all option-card {{ $selectedKesesuaian === 'belum_sesuai' ? 'border-[#db2777] bg-pink-50/40 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white' }}"
                               id="label-belum-sesuai">
                            <div class="flex items-center h-5">
                                <input type="radio"
                                       name="kesesuaian"
                                       value="belum_sesuai"
                                       class="h-4 w-4 text-[#db2777] border-slate-300 focus:ring-[#db2777]"
                                       {{ $selectedKesesuaian === 'belum_sesuai' ? 'checked' : '' }}
                                       onchange="updateOptionCards()">
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-semibold text-slate-900 block">Belum sesuai</span>
                                <span class="text-xs text-slate-500 mt-0.5 block">Laporan dibuka kembali dan dikirim ke Admin untuk ditindaklanjuti.</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Pertanyaan 2: Penilaian Layanan (Rating Bintang 1-5) --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-800 mb-2">
                        Penilaian layanan
                    </label>

                    <div class="flex items-center gap-1.5" id="starRatingContainer">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    class="star-btn p-1 text-2xl transition-transform hover:scale-110 focus:outline-none select-none {{ $i <= $selectedRating ? 'text-amber-400' : 'text-slate-300' }}"
                                    data-value="{{ $i }}"
                                    title="{{ $i }} Bintang">
                                &#9733;
                            </button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="{{ $selectedRating }}">
                </div>

                {{-- Pertanyaan 3: Komentar --}}
                <div class="mb-6">
                    <label for="komentar" class="block text-sm font-semibold text-slate-800 mb-2">
                        Komentar <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="komentar"
                              id="komentar"
                              rows="4"
                              required
                              placeholder="Printer sudah normal kembali, terima kasih atas penanganannya yang cepat."
                              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:border-[#db2777] focus:outline-none focus:ring-1 focus:ring-[#db2777]">{{ $komentarText }}</textarea>
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-[#e11467] hover:bg-[#c90f58] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#e11467] focus:ring-offset-2">
                        {{ $existingComment ? 'Simpan Pembaruan' : 'Kirim Tanggapan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function updateOptionCards() {
        const sudah = document.querySelector('input[value="sudah_sesuai"]');
        const cardSudah = document.getElementById('label-sudah-sesuai');
        const cardBelum = document.getElementById('label-belum-sesuai');

        if (sudah && sudah.checked) {
            cardSudah.className = 'relative flex items-start p-4 rounded-xl border cursor-pointer transition-all option-card border-[#db2777] bg-pink-50/40 shadow-sm';
            cardBelum.className = 'relative flex items-start p-4 rounded-xl border cursor-pointer transition-all option-card border-slate-200 hover:border-slate-300 bg-white';
        } else {
            cardSudah.className = 'relative flex items-start p-4 rounded-xl border cursor-pointer transition-all option-card border-slate-200 hover:border-slate-300 bg-white';
            cardBelum.className = 'relative flex items-start p-4 rounded-xl border cursor-pointer transition-all option-card border-[#db2777] bg-pink-50/40 shadow-sm';
        }
    }

    // Interaktivitas Star Rating
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('starRatingContainer');
        const ratingInput = document.getElementById('ratingInput');
        if (!container || !ratingInput) return;

        const stars = container.querySelectorAll('.star-btn');

        function setStars(val) {
            stars.forEach(s => {
                const sVal = parseInt(s.getAttribute('data-value'));
                if (sVal <= val) {
                    s.classList.remove('text-slate-300');
                    s.classList.add('text-amber-400');
                } else {
                    s.classList.remove('text-amber-400');
                    s.classList.add('text-slate-300');
                }
            });
        }

        stars.forEach(star => {
            star.addEventListener('click', function () {
                const val = parseInt(this.getAttribute('data-value'));
                ratingInput.value = val;
                setStars(val);
            });

            star.addEventListener('mouseenter', function () {
                const val = parseInt(this.getAttribute('data-value'));
                setStars(val);
            });
        });

        container.addEventListener('mouseleave', function () {
            const current = parseInt(ratingInput.value) || 0;
            setStars(current);
        });
    });
</script>
@endpush
