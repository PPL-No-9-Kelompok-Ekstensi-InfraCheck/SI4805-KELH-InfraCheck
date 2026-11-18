@extends('layouts.app')

@section('title', 'Perangkat')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Data Perangkat</h2>
            <p class="text-sm text-slate-500 mt-1">Inventaris perangkat teknologi kampus beserta lokasi dan kondisinya.</p>
        </div>
        <a href="{{ route('devices.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm transition-colors">
            + Tambah Perangkat
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode Inventaris</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Perangkat</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kondisi</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse ($devices as $device)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-6 py-3.5 font-mono text-xs text-slate-600">{{ $device->kode_aset }}</td>
                        <td class="px-6 py-3.5 font-medium text-slate-800">{{ $device->nama_perangkat }}</td>
                        <td class="px-6 py-3.5 text-slate-600">
                            {{ $device->jenis_label }}
                            @if ($device->kategori_label)
                                <span class="block text-xs text-slate-400">{{ $device->kategori_label }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-slate-600">
                            {{ $device->lokasi_label }}
                            @if ($device->gedung_label)
                                <span class="block text-xs text-slate-400">{{ $device->gedung_label }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $device->kondisi?->badgeClass() ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $device->kondisi?->label() ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-right whitespace-nowrap">
                            <a href="{{ route('devices.edit', $device) }}"
                               class="text-blue-600 hover:text-blue-700 text-sm font-medium mr-4">Edit</a>
                            <form action="{{ route('devices.destroy', $device) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus perangkat ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-sm">Belum ada data perangkat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($devices->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $devices->links() }}
            </div>
        @endif
    </div>
@endsection
