@extends('layouts.app')

@section('title', 'Tambah Perangkat')

@section('content')
    <div class="max-w-lg">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Tambah Perangkat</h2>

        <form action="{{ route('devices.store') }}" method="POST"
              class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6 space-y-5">
            @csrf

            <div>
                <label for="kode_aset" class="block text-sm font-medium text-slate-700 mb-1">Kode Inventaris</label>
                <input type="text" name="kode_aset" id="kode_aset" value="{{ old('kode_aset') }}"
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="mis. AST-AP-002">
                @error('kode_aset')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nama_perangkat" class="block text-sm font-medium text-slate-700 mb-1">Nama Perangkat</label>
                <input type="text" name="nama_perangkat" id="nama_perangkat" value="{{ old('nama_perangkat') }}"
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="mis. Access Point Cisco AP-02">
                @error('nama_perangkat')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="id_jenis" class="block text-sm font-medium text-slate-700 mb-1">Jenis Perangkat</label>
                <select name="id_jenis" id="id_jenis" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">-- Pilih Jenis --</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->id_jenis }}" @selected(old('id_jenis') == $t->id_jenis)>
                            {{ $t->nama_jenis }}{{ $t->category ? ' — ' . $t->category->nama_kategori : '' }}
                        </option>
                    @endforeach
                </select>
                @error('id_jenis')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="id_ruangan" class="block text-sm font-medium text-slate-700 mb-1">Lokasi (Ruangan)</label>
                <select name="id_ruangan" id="id_ruangan" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">-- Pilih Ruangan --</option>
                    @foreach ($rooms as $r)
                        <option value="{{ $r->id_ruangan }}" @selected(old('id_ruangan') == $r->id_ruangan)>
                            {{ $r->nama_ruangan }}{{ $r->building ? ' — ' . $r->building->nama_gedung : '' }}
                        </option>
                    @endforeach
                </select>
                @error('id_ruangan')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="kondisi" class="block text-sm font-medium text-slate-700 mb-1">Kondisi</label>
                <select name="kondisi" id="kondisi" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">-- Pilih Kondisi --</option>
                    @foreach ($kondisi as $k)
                        <option value="{{ $k }}" @selected(old('kondisi') === $k)>{{ $k }}</option>
                    @endforeach
                </select>
                @error('kondisi')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm">
                    Simpan
                </button>
                <a href="{{ route('devices.index') }}" class="text-slate-600 hover:text-slate-800 text-sm">Batal</a>
            </div>
        </form>
    </div>
@endsection
