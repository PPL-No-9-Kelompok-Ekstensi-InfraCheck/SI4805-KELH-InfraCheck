@extends('buildings.layout')

@section('title', 'Data Gedung')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-600">Kelola kode dan nama gedung kampus. Gedung yang memiliki ruangan tidak dapat dihapus.</p>
        <a href="{{ route('buildings.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Tambah Gedung</a>
    </div>
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form action="{{ route('buildings.index') }}" method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1">
                <label for="q" class="mb-1.5 block text-sm font-medium">Cari gedung</label>
                <input id="q" name="q" type="search" value="{{ $search }}" maxlength="255" placeholder="Kode atau nama gedung" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari</button>
            @if ($search !== '')
                <a href="{{ route('buildings.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
            @endif
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Daftar gedung kampus</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th scope="col" class="p-3">Kode Gedung</th>
                        <th scope="col" class="p-3">Nama Gedung</th>
                        <th scope="col" class="p-3">Jumlah Ruangan</th>
                        <th scope="col" class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($buildings as $building)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-semibold">{{ $building->kode_gedung }}</td>
                            <td class="p-3">{{ $building->nama_gedung }}</td>
                            <td class="p-3">{{ $building->rooms_count }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('buildings.edit', $building) }}" class="font-medium text-blue-700 hover:underline" aria-label="Edit gedung {{ $building->kode_gedung }}">Edit</a>
                                    <form action="{{ route('buildings.destroy', $building) }}" method="POST" onsubmit="return confirm('Hapus gedung ini? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-700 hover:underline" aria-label="Hapus gedung {{ $building->kode_gedung }}">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-10 text-center text-slate-500">
                                {{ $search !== '' ? 'Tidak ada gedung yang sesuai dengan pencarian.' : 'Belum ada data gedung. Tambahkan gedung pertama Anda.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5 space-y-3">
            <p class="text-sm text-slate-500">Total {{ $buildings->total() }} gedung</p>
            {{ $buildings->links() }}
        </div>
    </section>
@endsection
