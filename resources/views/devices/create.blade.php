@extends('layouts.app')

@section('title', 'Tambah Perangkat')

@section('content')
    <div class="max-w-lg">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Tambah Perangkat</h2>

        <form action="{{ route('devices.store') }}" method="POST"
              class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6 space-y-5">
            @csrf

            @include('devices._form')

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm">
                    Simpan
                </button>
                <a href="{{ route('devices.index') }}" class="text-slate-600 hover:text-slate-800 text-sm">Batal</a>
            </div>
        </form>
    </div>
@endsection
