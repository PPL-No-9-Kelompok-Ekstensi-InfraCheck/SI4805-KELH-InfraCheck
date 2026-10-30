@props(['value' => 0])

@php
    $value = max(0, min(100, (int) $value));
    $color = $value >= 100 ? 'bg-emerald-500' : ($value >= 50 ? 'bg-blue-600' : 'bg-amber-500');
@endphp

<div {{ $attributes->merge(['class' => 'w-full bg-slate-100 rounded-full h-2 overflow-hidden']) }}>
    <div class="{{ $color }} h-2 rounded-full transition-all" style="width: {{ $value }}%;"></div>
</div>
