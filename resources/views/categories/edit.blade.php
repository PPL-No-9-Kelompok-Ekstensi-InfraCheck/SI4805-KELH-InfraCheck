@extends('layouts.app')

@section('title', 'Edit Kategori Infrastruktur')

@section('content')
    <div class="max-w-xl">
        <h2 class="mb-6 text-xl font-bold text-slate-900">Edit Kategori Infrastruktur</h2>
        <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-5 rounded-xl border border-slate-200/80 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            @include('categories._form')
        </form>
    </div>
@endsection
