<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — InfraCheck</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased md:flex">
    <aside class="bg-[#1e40af] p-5 text-white md:min-h-screen md:w-64 md:shrink-0">
        <a href="{{ route('dashboard') }}" class="text-2xl font-bold">InfraCheck</a>
        <p class="mt-1 text-xs text-blue-200">Masuk sebagai {{ auth()->user()->role?->nama_peran }}</p>
        <nav class="mt-7 space-y-2" aria-label="Navigasi utama">
            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3.5 py-2.5 text-sm hover:bg-blue-700">Dashboard</a>
            <p class="px-3.5 pt-4 text-xs font-bold tracking-wider text-blue-200">MASTER DATA</p>
            <a href="{{ route('buildings.index') }}" aria-current="page" class="block rounded-lg bg-blue-600/70 px-3.5 py-2.5 text-sm font-medium">Gedung</a>
            <form action="{{ route('logout') }}" method="POST" class="pt-5">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3.5 py-2.5 text-left text-sm hover:bg-red-600">Logout</button>
            </form>
        </nav>
    </aside>
    <main class="min-w-0 flex-1">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-6 py-5 md:px-8">
            <h1 class="text-xl font-bold text-slate-900">@yield('title')</h1>
            <span class="text-sm text-slate-600">{{ auth()->user()->nama }}</span>
        </header>
        <div class="max-w-7xl space-y-5 p-6 md:p-8">
            @if (session('success'))
                <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Periksa kembali data yang Anda masukkan.</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</body>
</html>
