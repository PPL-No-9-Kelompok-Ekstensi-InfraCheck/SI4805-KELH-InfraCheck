@extends('rooms.layout')

@section('title', 'Data Ruangan')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-600">Kelola ruangan dan gedung kampus. Ruangan yang direferensikan perangkat atau laporan tidak dapat dihapus.</p>
        <a href="{{ route('rooms.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Tambah Ruangan</a>
    </div>
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form action="{{ route('rooms.index') }}" method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1">
                <label for="q" class="mb-1.5 block text-sm font-medium">Cari ruangan</label>
                <input id="q" name="q" type="search" value="{{ $search }}" maxlength="255" placeholder="Kode/nama ruangan atau gedung" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari</button>
            @if ($search !== '')
                <a href="{{ route('rooms.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
            @endif
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Daftar ruangan kampus</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th scope="col" class="p-3">Kode Ruangan</th>
                        <th scope="col" class="p-3">Nama Ruangan</th>
                        <th scope="col" class="p-3">Gedung</th>
                        <th scope="col" class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rooms as $room)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-semibold">{{ $room->kode_ruangan }}</td>
                            <td class="p-3">{{ $room->nama_ruangan }}</td>
                            <td class="p-3">{{ $room->building->kode_gedung }} — {{ $room->building->nama_gedung }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('rooms.edit', $room) }}" class="font-medium text-blue-700 hover:underline" aria-label="Edit ruangan {{ $room->kode_ruangan }}">Edit</a>
                                    <form action="{{ route('rooms.destroy', $room) }}" method="POST" onsubmit="return confirm('Hapus ruangan ini? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-700 hover:underline" aria-label="Hapus ruangan {{ $room->kode_ruangan }}">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-10 text-center text-slate-500">
                                {{ $search !== '' ? 'Tidak ada ruangan yang sesuai dengan pencarian.' : 'Belum ada data ruangan. Tambahkan ruangan pertama Anda.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5 space-y-3">
            <p class="text-sm text-slate-500">Total {{ $rooms->total() }} ruangan</p>
            {{ $rooms->links() }}
        </div>
    </section>
@endsection
