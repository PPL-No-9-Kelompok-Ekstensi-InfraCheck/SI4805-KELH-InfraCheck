@php
    $oldName = old('nama_kategori', $category->nama_kategori ?? '');
    $nameValue = is_string($oldName) ? $oldName : '';
@endphp

<div>
    <label for="nama_kategori" class="mb-2 block text-sm font-semibold">Nama Kategori <span class="text-red-600">*</span></label>
    <input id="nama_kategori" name="nama_kategori" type="text" value="{{ $nameValue }}" required maxlength="255" autofocus aria-invalid="{{ $errors->has('nama_kategori') ? 'true' : 'false' }}" aria-describedby="nama_kategori-help{{ $errors->has('nama_kategori') ? ' nama_kategori-error' : '' }}" placeholder="Contoh: Jaringan & Internet" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
    @error('nama_kategori')
        <p id="nama_kategori-error" class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p>
    @enderror
    <p id="nama_kategori-help" class="mt-2 text-xs text-slate-500">Nama kategori wajib diisi, unik, dan maksimal 255 karakter.</p>
</div>
<div class="flex items-center gap-3 border-t border-slate-100 pt-5">
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
    <a href="{{ route('categories.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm hover:bg-slate-50">Batal</a>
</div>
