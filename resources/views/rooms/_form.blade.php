@php
    $oldBuilding = old('id_gedung', $room->id_gedung ?? '');
    $oldCode = old('kode_ruangan', $room->kode_ruangan ?? '');
    $oldName = old('nama_ruangan', $room->nama_ruangan ?? '');
    $buildingValue = is_string($oldBuilding) || is_int($oldBuilding) ? (string) $oldBuilding : '';
    $codeValue = is_string($oldCode) ? $oldCode : '';
    $nameValue = is_string($oldName) ? $oldName : '';
@endphp

@if ($buildings->isEmpty())
    <div role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Belum ada data gedung. <a href="{{ route('buildings.create') }}" class="font-semibold underline">Tambahkan gedung terlebih dahulu</a> sebelum menyimpan ruangan.
    </div>
@endif
<div>
    <label for="id_gedung" class="mb-2 block text-sm font-semibold">Gedung <span class="text-red-600">*</span></label>
    <select id="id_gedung" name="id_gedung" required autofocus aria-invalid="{{ $errors->has('id_gedung') ? 'true' : 'false' }}" @error('id_gedung') aria-describedby="id_gedung-error" @enderror class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
        <option value="">Pilih gedung</option>
        @foreach ($buildings as $building)
            <option value="{{ $building->id_gedung }}" @selected($buildingValue === (string) $building->id_gedung)>{{ $building->kode_gedung }} — {{ $building->nama_gedung }}</option>
        @endforeach
    </select>
    @error('id_gedung')
        <p id="id_gedung-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
<div>
    <label for="kode_ruangan" class="mb-2 block text-sm font-semibold">Kode Ruangan <span class="text-red-600">*</span></label>
    <input id="kode_ruangan" name="kode_ruangan" type="text" value="{{ $codeValue }}" required maxlength="255" aria-invalid="{{ $errors->has('kode_ruangan') ? 'true' : 'false' }}" @error('kode_ruangan') aria-describedby="kode_ruangan-error" @enderror class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
    @error('kode_ruangan')
        <p id="kode_ruangan-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
<div>
    <label for="nama_ruangan" class="mb-2 block text-sm font-semibold">Nama Ruangan <span class="text-red-600">*</span></label>
    <input id="nama_ruangan" name="nama_ruangan" type="text" value="{{ $nameValue }}" required maxlength="255" aria-invalid="{{ $errors->has('nama_ruangan') ? 'true' : 'false' }}" @error('nama_ruangan') aria-describedby="nama_ruangan-error" @enderror class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
    @error('nama_ruangan')
        <p id="nama_ruangan-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
<p class="text-xs text-slate-500">Pilih gedung yang tersedia. Kode dan nama ruangan maksimal 255 karakter.</p>
<div class="flex items-center gap-3 border-t border-slate-100 pt-5">
    <button type="submit" @disabled($buildings->isEmpty()) class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">Simpan</button>
    <a href="{{ route('rooms.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm hover:bg-slate-50">Batal</a>
</div>
