@extends('buildings.layout')

@section('title', 'Tambah Gedung')

@section('content')
    <form action="{{ route('buildings.store') }}" method="POST" class="max-w-2xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('buildings._form')
    </form>
@endsection
