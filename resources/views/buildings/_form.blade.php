@php
    $oldCode = old('kode_gedung', $building->kode_gedung ?? '');
    $oldName = old('nama_gedung', $building->nama_gedung ?? '');
    $codeValue = is_string($oldCode) ? $oldCode : '';
    $nameValue = is_string($oldName) ? $oldName : '';
@endphp

<div>
    <label for="kode_gedung" class="mb-2 block text-sm font-semibold">Kode Gedung <span class="text-red-600">*</span></label>
    <input id="kode_gedung" name="kode_gedung" type="text" value="{{ $codeValue }}" required maxlength="255" autofocus aria-invalid="{{ $errors->has('kode_gedung') ? 'true' : 'false' }}" @error('kode_gedung') aria-describedby="kode_gedung-error" @enderror class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
    @error('kode_gedung')
        <p id="kode_gedung-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
<div>
    <label for="nama_gedung" class="mb-2 block text-sm font-semibold">Nama Gedung <span class="text-red-600">*</span></label>
    <input id="nama_gedung" name="nama_gedung" type="text" value="{{ $nameValue }}" required maxlength="255" aria-invalid="{{ $errors->has('nama_gedung') ? 'true' : 'false' }}" @error('nama_gedung') aria-describedby="nama_gedung-error" @enderror class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
    @error('nama_gedung')
        <p id="nama_gedung-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
<p class="text-xs text-slate-500">Kode gedung harus unik. Kode dan nama gedung maksimal 255 karakter.</p>
<div class="flex items-center gap-3 border-t border-slate-100 pt-5">
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
    <a href="{{ route('buildings.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm hover:bg-slate-50">Batal</a>
</div>
