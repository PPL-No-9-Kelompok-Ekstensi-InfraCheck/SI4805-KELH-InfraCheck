@extends('rooms.layout')

@section('title', 'Edit Ruangan')

@section('content')
    <form action="{{ route('rooms.update', $room) }}" method="POST" class="max-w-2xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('rooms._form')
    </form>
@endsection
