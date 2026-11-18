@extends('layouts.app')

@section('title', 'Perbarui Catatan Progres')
@section('page_title', 'Progres Perbaikan')

@section('content')
    <div class="mb-6">
        <a href="{{ route('progress.show', $assignment) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors">
            &larr; Kembali ke detail progres
        </a>
    </div>

    @include('progress._form', [
        'action' => route('progress.update', [$assignment, $progress]),
        'method' => 'PUT',
        'progress' => $progress,
        'submitLabel' => 'Perbarui Catatan',
    ])
@endsection
