@extends('layouts.app')

@section('judul', 'Buat Laporan')

@section('konten')
<div class="max-w-5xl">

    <!-- Banner berhasil / kesalahan -->
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-600 text-sm font-medium">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('status'))
        <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-start gap-2">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
                {{ session('status') }}
                Nomor laporan Anda: <strong>{{ session('nomor_laporan') }}</strong>.
                Laporan akan diverifikasi oleh Admin Unit IT.
            </span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

        <!-- ==================== FORMULIR LAPORAN ==================== -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <h3 class="text-base font-bold text-slate-900 mb-1">Formulir Pengajuan Laporan Kerusakan</h3>
            <p class="text-xs text-slate-500 mb-6">
                Laporkan kerusakan infrastruktur teknologi di lingkungan kampus.
                Pastikan data yang Anda isi sesuai dengan kondisi perangkat yang ditemukan.
            </p>

            <form action="{{ route('reports.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Pilih Perangkat -->
                <div>
                    <label for="id_perangkat" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Perangkat yang Rusak <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="id_perangkat"
                        name="id_perangkat"
                        required
                        class="w-full px-3.5 py-2.5 text-sm text-slate-800 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('id_perangkat') border-red-400 @enderror"
                    >
                        <option value="">— Pilih perangkat —</option>
                        @php
                            $perangkatTerstruktur = $devices->groupBy(
                                fn ($d) => ($d->room?->building?->nama_gedung ?? 'Tanpa Gedung') . ' — ' . ($d->room?->nama_ruangan ?? 'Tanpa Ruangan')
                            );
                        @endphp
                        @foreach ($perangkatTerstruktur as $lokasi => $daftar)
                            <optgroup label="{{ $lokasi }}">
                                @foreach ($daftar as $device)
                                    <option value="{{ $device->id_perangkat }}" @selected(old('id_perangkat') == $device->id_perangkat)>
                                        {{ $device->kode_aset }} · {{ $device->nama_perangkat }} ({{ $device->deviceType?->nama_jenis ?? 'Umum' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('id_perangkat')
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror

                    <!-- Peringatan laporan ganda (diisi via JavaScript) -->
                    <div id="peringatanDuplikat" class="hidden mt-2 p-3 rounded-lg bg-amber-50 border border-amber-300 text-amber-800 text-xs font-medium">
                        <strong>Laporan ganda terdeteksi.</strong>
                        <span id="detailDuplikat"></span>
                    </div>
                    <p id="budPerangkatAman" class="hidden mt-2 text-xs font-medium text-emerald-600">
                        Aman — belum ada laporan aktif pada perangkat ini.
                    </p>
                </div>

                <!-- Tingkat Urgensi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tingkat Urgensi <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @php
                            $warnaUrgensi = [
                                'Rendah' => 'peer-checked:bg-slate-100 peer-checked:border-slate-400 peer-checked:text-slate-700',
                                'Sedang' => 'peer-checked:bg-blue-50 peer-checked:border-blue-400 peer-checked:text-blue-700',
                                'Tinggi' => 'peer-checked:bg-amber-50 peer-checked:border-amber-400 peer-checked:text-amber-700',
                                'Kritis' => 'peer-checked:bg-red-50 peer-checked:border-red-400 peer-checked:text-red-600',
                            ];
                    @endphp
                    @foreach ($urgensiLevels as $urgensi)
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="id_urgensi"
                                value="{{ $urgensi->id_urgensi }}"
                                class="peer sr-only"
                                @checked(old('id_urgensi') == $urgensi->id_urgensi)
                            >
                            <span class="flex items-center justify-center px-3 py-2.5 rounded-lg border border-slate-300 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all {{ $warnaUrgensi[$urgensi->nama_urgensi] ?? '' }}">
                                {{ $urgensi->nama_urgensi }}
                            </span>
                        </label>
                    @endforeach

                    {{-- Safelist agar Tailwind CDN tetap menghasilkan class peer-checked yang dinamis --}}
                    <div class="hidden peer-checked:bg-slate-100 peer-checked:border-slate-400 peer-checked:text-slate-700 peer-checked:bg-blue-50 peer-checked:border-blue-400 peer-checked:text-blue-700 peer-checked:bg-amber-50 peer-checked:border-amber-400 peer-checked:text-amber-700 peer-checked:bg-red-50 peer-checked:border-red-400 peer-checked:text-red-600"></div>
                </div>
                @error('id_urgensi')
                    <p class="text-xs font-medium text-red-500 -mt-3">{{ $message }}</p>
                @enderror

                <!-- Deskripsi Kerusakan -->
                <div>
                    <label for="deskripsi_kerusakan" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Deskripsi Kerusakan <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="deskripsi_kerusakan"
                        name="deskripsi_kerusakan"
                        rows="5"
                        maxlength="5000"
                        placeholder="Jelaskan gejala kerusakan, waktu ditemukan, dan dampaknya. Contoh: Proyektor tidak menyala sama sekali sejak pagi, lampu indikator merah berkedip 3x."
                        class="w-full px-3.5 py-2.5 text-sm text-slate-800 bg-white border border-slate-300 rounded-lg placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('deskripsi_kerusakan') border-red-400 @enderror"
                    >{{ old('deskripsi_kerusakan') }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-400 text-right">
                        <span id="hitungKarakter">0</span> / 5000 karakter
                    </p>
                    @error('deskripsi_kerusakan')
                        <p class="text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Foto Dokumentasi -->
                <div>
                    <label for="foto" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Foto Dokumentasi Kerusakan <span class="text-slate-400 font-normal">(opsional, maks. 5 foto)</span>
                    </label>
                    <label
                        for="foto"
                        id="areaUnggah"
                        class="flex flex-col items-center justify-center gap-2 px-6 py-8 rounded-xl border-2 border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/40 cursor-pointer transition-all"
                    >
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 9.75h.008v.008H18V9.75zM3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                        </svg>
                        <span class="text-xs text-slate-500">
                            Klik untuk memilih foto (JPG / PNG / WEBP, maks. 2 MB per foto)
                        </span>
                        <input
                            type="file"
                            id="foto"
                            name="foto[]"
                            accept="image/jpg,image/jpeg,image/png,image/webp"
                            multiple
                            class="sr-only"
                        >
                    </label>

                    <!-- Pratinjau foto (diisi via JavaScript) -->
                    <div id="pratinjauFoto" class="hidden mt-3 grid grid-cols-3 sm:grid-cols-5 gap-3"></div>
                    @error('foto.*')
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol aksi -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#e11d48] hover:bg-[#be123c] text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-150 active:scale-[0.99] text-sm tracking-wide"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                        Kirim Laporan
                    </button>
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <!-- ==================== PANEL SISI: PANDUAN ==================== -->
        <div class="space-y-5">

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Alur Laporan</h3>
                <ol class="space-y-4 text-xs text-slate-600">
                    <li class="flex gap-3">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center">1</span>
                        <span>Ajukan laporan dengan memilih perangkat dan mengisi deskripsi.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center">2</span>
                        <span>Laporan Anda akan ditandai <strong>Menunggu Verifikasi</strong>.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center">3</span>
                        <span>Admin Unit IT memverifikasi, menentukan prioritas, lalu menugaskan teknisi.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center">4</span>
                        <span>Pantau perkembangan laporan melalui menu <strong>Status Laporan</strong>.</span>
                    </li>
                </ol>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Peringatan Laporan Ganda</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Satu perangkat hanya boleh memiliki <strong>satu laporan aktif</strong>.
                    Sistem akan otomatis menolak pengajuan jika perangkat yang Anda pilih masih
                    memiliki laporan yang sedang diverifikasi atau diperbaiki.
                </p>
            </div>

        </div>

    </div>
</div>
@endsection

@push('skrip')
<script>
    // ==================== Deteksi laporan ganda ====================
    const pilihPerangkat = document.getElementById('id_perangkat');
    const peringatanDuplikat = document.getElementById('peringatanDuplikat');
    const detailDuplikat = document.getElementById('detailDuplikat');
    const budPerangkatAman = document.getElementById('budPerangkatAman');

    async function cekDuplikat() {
        const idPerangkat = pilihPerangkat.value;

        // Sembunyikan semua status ketika belum memilih perangkat
        peringatanDuplikat.classList.add('hidden');
        budPerangkatAman.classList.add('hidden');
        if (!idPerangkat) return;

        try {
            const respons = await fetch(
                "{{ route('reports.check') }}?id_perangkat=" + encodeURIComponent(idPerangkat),
                { headers: { 'Accept': 'application/json' } }
            );
            const data = await respons.json();

            if (data.duplikat) {
                detailDuplikat.textContent =
                    ' Perangkat ini sudah memiliki laporan ' + data.nomor_laporan +
                    ' dengan status "' + data.nama_status + '".';
                peringatanDuplikat.classList.remove('hidden');
            } else {
                budPerangkatAman.classList.remove('hidden');
            }
        } catch (galat) {
            // Gagal memeriksa tidak menghalangi pengiriman; validasi server tetap berjalan
            console.warn('Gagal memeriksa laporan ganda:', galat);
        }
    }

    pilihPerangkat.addEventListener('change', cekDuplikat);

    // Periksa ulang laporan ganda saat halaman dimuat dengan nilai lama (validasi gagal)
    if (pilihPerangkat.value) {
        cekDuplikat();
    }

    // ==================== Penghitung karakter deskripsi ====================
    const deskripsi = document.getElementById('deskripsi_kerusakan');
    const hitungKarakter = document.getElementById('hitungKarakter');

    function perbaruiHitung() {
        hitungKarakter.textContent = deskripsi.value.length;
    }
    deskripsi.addEventListener('input', perbaruiHitung);
    perbaruiHitung();

    // ==================== Pratinjau foto & pembatasan jumlah ====================
    const inputFoto = document.getElementById('foto');
    const pratinjauFoto = document.getElementById('pratinjauFoto');

    inputFoto.addEventListener('change', function () {
        const berkas = Array.from(inputFoto.files);

        // Batasi maksimal 5 foto
        if (berkas.length > 5) {
            alert('Maksimal 5 foto yang dapat diunggah.');
            inputFoto.value = '';
            pratinjauFoto.innerHTML = '';
            pratinjauFoto.classList.add('hidden');
            return;
        }

        pratinjauFoto.innerHTML = '';
        if (berkas.length === 0) {
            pratinjauFoto.classList.add('hidden');
            return;
        }

        berkas.forEach(function (berkasFoto) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(berkasFoto);
            img.className = 'h-20 w-full object-cover rounded-lg border border-slate-200';
            img.alt = 'Pratinjau foto dokumentasi';
            pratinjauFoto.appendChild(img);
        });

        pratinjauFoto.classList.remove('hidden');
    });
</script>
@endpush
