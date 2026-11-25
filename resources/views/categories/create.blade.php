@extends('layouts.app')

@section('title', 'Tambah Kategori Infrastruktur')

@section('content')
    <div class="max-w-xl">
        <h2 class="mb-6 text-xl font-bold text-slate-900">Tambah Kategori Infrastruktur</h2>
        <form action="{{ route('categories.store') }}" method="POST" class="space-y-5 rounded-xl border border-slate-200/80 bg-white p-6 shadow-sm">
            @csrf
            @include('categories._form')
        </form>
    </div>
@endsection
