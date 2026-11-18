@extends('layouts.app')

@section('title', 'Jenis Perangkat')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Jenis Perangkat</h2>
            <p class="text-sm text-slate-500 mt-1">Kelompokkan perangkat teknologi berdasarkan jenisnya.</p>
        </div>
        <a href="{{ route('device-types.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm transition-colors">
            + Tambah Jenis
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Jenis</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse ($types as $type)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-6 py-3.5 font-medium text-slate-800">{{ $type->nama_jenis }}</td>
                        <td class="px-6 py-3.5 text-slate-600">{{ $type->category->nama_kategori ?? '-' }}</td>
                        <td class="px-6 py-3.5 text-right">
                            <a href="{{ route('device-types.edit', $type) }}"
                               class="text-blue-600 hover:text-blue-700 text-sm font-medium mr-4">Edit</a>
                            <form action="{{ route('device-types.destroy', $type) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus jenis perangkat ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-slate-500 text-sm">Belum ada jenis perangkat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection