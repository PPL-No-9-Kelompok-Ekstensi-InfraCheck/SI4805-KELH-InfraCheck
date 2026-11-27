@extends('layouts.app')

@section('title', 'Kategori Infrastruktur')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Kategori Infrastruktur</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola kategori infrastruktur teknologi. Kategori yang digunakan oleh jenis perangkat tidak dapat dihapus.</p>
        </div>
        @if (auth()->user()->hasPermission('categories.create'))
            <a href="{{ route('categories.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ Tambah Kategori</a>
        @endif
    </div>

    <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <form action="{{ route('categories.index') }}" method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1">
                <label for="q" class="mb-1.5 block text-sm font-medium">Cari kategori</label>
                <input id="q" name="q" type="search" value="{{ $search }}" maxlength="255" placeholder="Nama kategori infrastruktur" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('q')
                    <p class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Cari</button>
            @if ($search !== '')
                <a href="{{ route('categories.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Daftar kategori infrastruktur teknologi</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th scope="col" class="p-3">Nama Kategori</th>
                        <th scope="col" class="p-3">Jumlah Jenis Perangkat</th>
                        <th scope="col" class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-medium text-slate-800">{{ $category->nama_kategori }}</td>
                            <td class="p-3">{{ $category->device_types_count }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    @if (auth()->user()->hasPermission('categories.update'))
                                        <a href="{{ route('categories.edit', $category) }}" class="font-medium text-blue-700 hover:underline" aria-label="Edit kategori {{ $category->nama_kategori }}">Edit</a>
                                    @endif
                                    @if (auth()->user()->hasPermission('categories.delete'))
                                        <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Hapus kategori ini? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-700 hover:underline" aria-label="Hapus kategori {{ $category->nama_kategori }}">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-10 text-center text-slate-500">{{ $search !== '' ? 'Tidak ada kategori yang sesuai dengan pencarian.' : 'Belum ada data kategori. Tambahkan kategori pertama Anda.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5 space-y-3">
            <p class="text-sm text-slate-500">Total {{ $categories->total() }} kategori</p>
            {{ $categories->links() }}
        </div>
    </section>
@endsection
