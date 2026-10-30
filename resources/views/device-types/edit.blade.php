@extends('layouts.app')

@section('title', 'Edit Jenis Perangkat')

@section('content')
    <div class="max-w-lg">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Edit Jenis Perangkat</h2>

        <form action="{{ route('device-types.update', $deviceType) }}" method="POST"
              class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="id_kategori" class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                <select name="id_kategori" id="id_kategori"
                        class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id_kategori }}" @selected(old('id_kategori', $deviceType->id_kategori) == $c->id_kategori)>
                            {{ $c->nama_kategori }}
                        </option>
                    @endforeach
                </select>
                @error('id_kategori')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nama_jenis" class="block text-sm font-medium text-slate-700 mb-1">Nama Jenis</label>
                <input type="text" name="nama_jenis" id="nama_jenis" value="{{ old('nama_jenis', $deviceType->nama_jenis) }}"
                       class="w-full rounded-lg border-slate-300 text-sm">
                @error('nama_jenis')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm">
                    Perbarui
                </button>
                <a href="{{ route('device-types.index') }}" class="text-slate-600 hover:text-slate-800 text-sm">Batal</a>
            </div>
        </form>
    </div>
@endsection