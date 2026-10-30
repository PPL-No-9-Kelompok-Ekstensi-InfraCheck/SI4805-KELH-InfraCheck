<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'InfraCheck') — InfraCheck</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        sidebar: '#1d4ed8',
                        sidebarHover: '#1e40af',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }
    </style>
</head>
<body class="min-h-screen flex antialiased text-slate-800">

    <aside class="w-64 bg-[#1e40af] text-white shrink-0 min-h-screen flex flex-col justify-between select-none">
        <div class="p-5">
            <div class="mb-7 pl-1">
                <h1 class="text-2xl font-bold tracking-tight text-white">InfraCheck</h1>
                <p class="text-xs text-blue-200/90 mt-0.5">
                    Masuk sebagai {{ auth()->user()->role?->nama_peran ?? 'Pengguna' }}
                </p>
            </div>

            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center px-3.5 py-2.5 rounded-lg text-sm transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600/70 text-white font-medium shadow-sm' : 'text-blue-100 hover:bg-blue-700/60 hover:text-white' }}">
                    Dashboard
                </a>

                @if (auth()->user()->hasRole('Admin'))
                    <div class="pt-5 pb-1">
                        <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">MASTER DATA</p>
                    </div>
                    @foreach (['Gedung', 'Ruangan', 'Kategori Infrastruktur', 'Jenis Perangkat', 'Perangkat'] as $menu)
                        <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">{{ $menu }}</a>
                    @endforeach

                    <div class="pt-5 pb-1">
                        <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">LAPORAN</p>
                    </div>
                    @foreach (['Verifikasi Laporan', 'Prioritas Penanganan', 'Penugasan Teknisi', 'Jadwal Perbaikan'] as $menu)
                        <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">{{ $menu }}</a>
                    @endforeach
                @endif

                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">PEMANTAUAN</p>
                </div>
                <a href="{{ route('reports.status') }}"
                   class="flex items-center px-3.5 py-1.5 rounded-lg text-sm transition-colors {{ request()->routeIs('reports.*') ? 'bg-blue-600/70 text-white font-medium shadow-sm' : 'text-blue-100 hover:bg-blue-700/60 hover:text-white' }}">
                    Status Laporan
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Riwayat Penanganan
                </a>

                @if (auth()->user()->hasRole('Admin'))
                    <div class="pt-5 pb-1">
                        <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">PENGATURAN</p>
                    </div>
                    @foreach (['Pengguna', 'Hak Akses'] as $menu)
                        <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">{{ $menu }}</a>
                    @endforeach
                @endif

                <form action="{{ route('logout') }}" method="POST" class="pt-1">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center px-3.5 py-1.5 rounded-lg text-blue-200 hover:bg-red-600/80 hover:text-white text-sm transition-colors">
                        Logout
                    </button>
                </form>
            </nav>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-h-screen bg-[#f1f5f9] overflow-x-hidden">
        <header class="h-16 bg-white border-b border-slate-200/90 px-8 flex items-center justify-between sticky top-0 z-10">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">@yield('heading', 'InfraCheck')</h2>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-slate-600 font-bold text-xs uppercase">
                    {{ mb_substr(auth()->user()->nama, 0, 1) }}
                </div>
                <span class="text-sm font-medium text-slate-700">
                    {{ auth()->user()->short_name }} ({{ auth()->user()->role?->nama_peran ?? 'Pengguna' }})
                </span>
            </div>
        </header>

        <div class="p-8 max-w-7xl w-full">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
