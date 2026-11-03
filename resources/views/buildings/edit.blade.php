@extends('buildings.layout')

@section('title', 'Edit Gedung')

@section('content')
    <form action="{{ route('buildings.update', $building) }}" method="POST" class="max-w-2xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('buildings._form')
    </form>
@endsection
