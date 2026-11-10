<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'InfraCheck') — InfraCheck</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
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

    @php
        $user = auth()->user();
        $navIdle = 'flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors';
        $navActive = 'flex items-center px-3.5 py-2.5 rounded-lg bg-blue-600/70 text-white font-medium text-sm transition-colors shadow-sm';
    @endphp

    <!-- ==================== SIDEBAR ==================== -->
    <aside class="w-64 bg-[#1e40af] text-white shrink-0 min-h-screen flex flex-col justify-between select-none">
        <div class="p-5">
            <!-- Sidebar Brand Header -->
            <div class="mb-7 pl-1">
                <a href="{{ route('dashboard') }}" class="block">
                    <h1 class="text-2xl font-bold tracking-tight text-white">InfraCheck</h1>
                </a>
                <p class="text-xs text-blue-200/90 mt-0.5">
                    Masuk sebagai {{ $user->role->nama_peran ?? 'Admin' }}
                </p>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                    Dashboard
                </a>

                <!-- SECTION: MASTER DATA -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        MASTER DATA
                    </p>
                </div>
                <a href="#" class="{{ $navIdle }}">Gedung</a>
                <a href="#" class="{{ $navIdle }}">Ruangan</a>
                <a href="#" class="{{ $navIdle }}">Kategori Infrastruktur</a>
                <a href="#" class="{{ $navIdle }}">Jenis Perangkat</a>
                <a href="#" class="{{ $navIdle }}">Perangkat</a>

                <!-- SECTION: LAPORAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        LAPORAN
                    </p>
                </div>
                <a href="#" class="{{ $navIdle }}">Verifikasi Laporan</a>
                <a href="#" class="{{ $navIdle }}">Prioritas Penanganan</a>
                <a href="#" class="{{ $navIdle }}">Penugasan Teknisi</a>
                <a href="{{ route('schedules.index') }}" class="{{ request()->routeIs('schedules.*') ? $navActive : $navIdle }}">
                    {{ $user->hasRole('Teknisi') ? 'Jadwal Saya' : 'Jadwal Perbaikan' }}
                </a>

                <!-- SECTION: PEMANTAUAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        PEMANTAUAN
                    </p>
                </div>
                <a href="{{ route('progress.index') }}" class="{{ request()->routeIs('progress.*') ? $navActive : $navIdle }}">
                    Progres Perbaikan
                </a>
                <a href="#" class="{{ $navIdle }}">Status Laporan</a>
                <a href="#" class="{{ $navIdle }}">Riwayat Penanganan</a>

                <!-- SECTION: PENGATURAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        PENGATURAN
                    </p>
                </div>
                <a href="#" class="{{ $navIdle }}">Pengguna</a>
                <a href="#" class="{{ $navIdle }}">Hak Akses</a>

                <!-- Logout Button Form -->
                <form action="{{ route('logout') }}" method="POST" class="pt-1">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center px-3.5 py-1.5 rounded-lg text-blue-200 hover:bg-red-600/80 hover:text-white text-sm transition-colors">
                        Logout
                    </button>
                </form>
            </nav>
        </div>
    </aside>

    <!-- ==================== MAIN CONTENT AREA ==================== -->
    <main class="flex-1 flex flex-col min-h-screen bg-[#f1f5f9] overflow-x-hidden">

        <!-- Top Navigation Bar -->
        <header class="h-16 bg-white border-b border-slate-200/90 px-8 flex items-center justify-between sticky top-0 z-10">
            <!-- Page Title -->
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">@yield('page_title', 'Dashboard')</h2>

            <!-- User Profile Chip -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-slate-600 font-bold text-xs uppercase">
                    {{ substr($user->nama, 0, 1) }}
                </div>
                <span class="text-sm font-medium text-slate-700">
                    {{ $user->nama }} ({{ $user->role->nama_peran ?? 'Admin' }})
                </span>
            </div>
        </header>

        <!-- Main Body -->
        <div class="p-8 max-w-7xl w-full">

            @if (session('success'))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <span class="mt-0.5 font-bold">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <span class="mt-0.5 font-bold">!</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </div>

    </main>

</body>
</html>
