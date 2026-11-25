@extends('layouts.app')

@section('judul', 'Edit Laporan')

@php
    $fotoDihapusLama = array_map('strval', (array) old('hapus_foto', []));
    $jumlahFotoAwal = $laporan->photos->count();
@endphp

@section('konten')
<div class="max-w-5xl space-y-6">

    <!-- Navigasi kembali & header nomor laporan -->
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('reports.show', $laporan->id_laporan) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Detail Laporan
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h3 class="text-xl font-bold text-slate-900">Perbarui {{ $laporan->nomor_laporan }}</h3>
                <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold border bg-amber-50 text-amber-700 border-amber-200">
                    {{ $laporan->status?->nama_status ?? 'Menunggu Verifikasi' }}
                </span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Diajukan {{ $laporan->waktu_laporan?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
            </p>
        </div>
    </div>

    <!-- Banner kesalahan validasi -->
    @if ($errors->any())
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-600 text-sm font-medium">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

        <!-- ==================== FORMULIR PEMBARUAN LAPORAN ==================== -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
            <h3 class="text-base font-bold text-slate-900 mb-1">Formulir Pembaruan Informasi Laporan</h3>
            <p class="text-xs text-slate-500 mb-6">
                Perbarui informasi kerusakan sebelum laporan diverifikasi oleh Admin Unit IT.
                Pastikan perangkat, tingkat urgensi, deskripsi, dan foto dokumentasi sudah sesuai.
            </p>

            <form action="{{ route('reports.update', $laporan->id_laporan) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

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
                            $perangkatTerpilih = old('id_perangkat', $laporan->id_perangkat);
                        @endphp
                        @foreach ($perangkatTerstruktur as $lokasi => $daftar)
                            <optgroup label="{{ $lokasi }}">
                                @foreach ($daftar as $device)
                                    <option value="{{ $device->id_perangkat }}" @selected($perangkatTerpilih == $device->id_perangkat)>
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
                        Aman — tidak ada laporan aktif lain pada perangkat ini.
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
                            $urgensiTerpilih = old('id_urgensi', $laporan->id_urgensi);
                        @endphp
                        @foreach ($urgensiLevels as $urgensi)
                            <label class="cursor-pointer">
                                <input
                                    type="radio"
                                    name="id_urgensi"
                                    value="{{ $urgensi->id_urgensi }}"
                                    class="peer sr-only"
                                    @checked($urgensiTerpilih == $urgensi->id_urgensi)
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
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

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
                        placeholder="Jelaskan gejala kerusakan, waktu ditemukan, dan dampaknya."
                        class="w-full px-3.5 py-2.5 text-sm text-slate-800 bg-white border border-slate-300 rounded-lg placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('deskripsi_kerusakan') border-red-400 @enderror"
                    >{{ old('deskripsi_kerusakan', $laporan->deskripsi_kerusakan) }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-400 text-right">
                        <span id="hitungKarakter">0</span> / 5000 karakter
                    </p>
                    @error('deskripsi_kerusakan')
                        <p class="text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Foto Dokumentasi Saat Ini -->
                @if ($laporan->photos->isNotEmpty())
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="block text-xs font-semibold text-slate-700">
                                Foto Dokumentasi Saat Ini ({{ $jumlahFotoAwal }})
                            </span>
                            <span class="text-[11px] text-slate-400">
                                Centang foto yang ingin dihapus saat menyimpan perubahan
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach ($laporan->photos as $foto)
                                @php
                                    $ditandaiHapus = in_array((string) $foto->id_foto, $fotoDihapusLama, true);
                                @endphp
                                <div class="kartu-foto-lama rounded-lg border p-2 transition-all {{ $ditandaiHapus ? 'border-red-300 bg-red-50/50 opacity-70' : 'border-slate-200 bg-slate-50/60' }}">
                                    <a href="{{ asset($foto->lokasi_berkas) }}" target="_blank" rel="noopener" class="block">
                                        <img
                                            src="{{ asset($foto->lokasi_berkas) }}"
                                            alt="Foto dokumentasi laporan"
                                            class="h-24 w-full object-cover rounded-md border border-slate-200"
                                        >
                                    </a>
                                    <label class="mt-2 flex items-center gap-2 cursor-pointer text-xs text-slate-600 select-none">
                                        <input
                                            type="checkbox"
                                            name="hapus_foto[]"
                                            value="{{ $foto->id_foto }}"
                                            class="cek-hapus-foto rounded border-slate-300 text-red-600 focus:ring-red-500"
                                            @checked($ditandaiHapus)
                                        >
                                        <span class="font-medium text-red-600">Hapus foto</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tambah Foto Dokumentasi Baru -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="foto" class="block text-xs font-semibold text-slate-700">
                            Tambah Foto Dokumentasi Baru <span class="text-slate-400 font-normal">(opsional, total maks. 5 foto)</span>
                        </label>
                        <span id="infoSlotFoto" class="text-[11px] font-medium text-slate-500"></span>
                    </div>
                    <label
                        for="foto"
                        id="areaUnggah"
                        class="flex flex-col items-center justify-center gap-2 px-6 py-8 rounded-xl border-2 border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/40 cursor-pointer transition-all"
                    >
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 9.75h.008v.008H18V9.75zM3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                        </svg>
                        <span class="text-xs text-slate-500">
                            Klik untuk memilih foto tambahan (JPG / PNG / WEBP, maks. 2 MB per foto)
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

                    <!-- Pratinjau foto baru (diisi via JavaScript) -->
                    <div id="pratinjauFoto" class="hidden mt-3 grid grid-cols-3 sm:grid-cols-5 gap-3"></div>
                    @error('foto')
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
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
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('reports.show', $laporan->id_laporan) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <!-- ==================== PANEL SISI: KETENTUAN ==================== -->
        <div class="space-y-5">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Ketentuan Pembaruan</h3>
                <ul class="space-y-3 text-xs text-slate-600">
                    <li class="flex gap-2.5">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-amber-100 text-amber-700 font-bold flex items-center justify-center">1</span>
                        <span>Laporan hanya dapat diubah selama statusnya masih <strong>Menunggu Verifikasi</strong>.</span>
                    </li>
                    <li class="flex gap-2.5">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center">2</span>
                        <span>Mengubah perangkat akan otomatis menyesuaikan ruangan berdasarkan data aset perangkat tersebut.</span>
                    </li>
                    <li class="flex gap-2.5">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center">3</span>
                        <span>Anda dapat menghapus foto lama dan menambahkan foto baru hingga maksimal <strong>5 foto</strong> per laporan.</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Deteksi Laporan Ganda</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Jika Anda mengganti perangkat, sistem akan memastikan perangkat pengganti tidak sedang
                    memiliki laporan aktif lain yang belum selesai ditangani.
                </p>
            </div>
        </div>

    </div>
</div>
@endsection

@push('skrip')
<script>
    // ==================== Deteksi laporan ganda (kecualikan laporan ini) ====================
    const pilihPerangkat = document.getElementById('id_perangkat');
    const peringatanDuplikat = document.getElementById('peringatanDuplikat');
    const detailDuplikat = document.getElementById('detailDuplikat');
    const budPerangkatAman = document.getElementById('budPerangkatAman');
    const idLaporanSaatIni = {{ (int) $laporan->id_laporan }};

    async function cekDuplikat() {
        const idPerangkat = pilihPerangkat.value;

        peringatanDuplikat.classList.add('hidden');
        budPerangkatAman.classList.add('hidden');
        if (!idPerangkat) return;

        try {
            const url = "{{ route('reports.check') }}?id_perangkat=" + encodeURIComponent(idPerangkat)
                + "&kecualikan_laporan=" + encodeURIComponent(idLaporanSaatIni);
            const respons = await fetch(url, { headers: { 'Accept': 'application/json' } });
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
            console.warn('Gagal memeriksa laporan ganda:', galat);
        }
    }

    pilihPerangkat.addEventListener('change', cekDuplikat);
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

    // ==================== Pengelolaan foto lama & pratinjau foto baru ====================
    const inputFoto = document.getElementById('foto');
    const pratinjauFoto = document.getElementById('pratinjauFoto');
    const infoSlotFoto = document.getElementById('infoSlotFoto');
    const cekHapusFoto = Array.from(document.querySelectorAll('.cek-hapus-foto'));
    const jumlahFotoAwal = {{ $jumlahFotoAwal }};
    const maksFoto = 5;

    function hitungSisaSlot() {
        const jumlahDihapus = cekHapusFoto.filter((el) => el.checked).length;
        const tersisa = jumlahFotoAwal - jumlahDihapus;
        return Math.max(0, maksFoto - tersisa);
    }

    function perbaruiTampilanSlot() {
        const sisaSlot = hitungSisaSlot();
        infoSlotFoto.textContent = 'Sisa kuota unggah: ' + sisaSlot + ' foto';

        cekHapusFoto.forEach(function (checkbox) {
            const kartu = checkbox.closest('.kartu-foto-lama');
            if (!kartu) return;

            if (checkbox.checked) {
                kartu.classList.add('border-red-300', 'bg-red-50/50', 'opacity-70');
                kartu.classList.remove('border-slate-200', 'bg-slate-50/60');
            } else {
                kartu.classList.remove('border-red-300', 'bg-red-50/50', 'opacity-70');
                kartu.classList.add('border-slate-200', 'bg-slate-50/60');
            }
        });
    }

    cekHapusFoto.forEach(function (checkbox) {
        checkbox.addEventListener('change', perbaruiTampilanSlot);
    });
    perbaruiTampilanSlot();

    inputFoto.addEventListener('change', function () {
        const berkas = Array.from(inputFoto.files);
        const sisaSlot = hitungSisaSlot();

        if (berkas.length > sisaSlot) {
            alert('Total foto dokumentasi maksimal ' + maksFoto + ' foto. Anda hanya dapat menambah maksimal ' + sisaSlot + ' foto lagi.');
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
            img.alt = 'Pratinjau foto tambahan';
            pratinjauFoto.appendChild(img);
        });

        pratinjauFoto.classList.remove('hidden');
    });
</script>
@endpush
