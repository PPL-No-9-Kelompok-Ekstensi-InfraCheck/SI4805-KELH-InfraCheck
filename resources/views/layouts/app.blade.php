<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @hasSection('judul')
            @yield('judul')
        @else
            @yield('title', 'InfraCheck')
        @endif
        — InfraCheck
    </title>

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
    @stack('styles')
</head>
<body class="min-h-screen flex antialiased text-slate-800">

    <!-- ==================== SIDEBAR ==================== -->
    @include('partials.sidebar', ['active' => request()->routeIs('dashboard') ? 'dashboard' : ''])

    <!-- ==================== MAIN CONTENT AREA ==================== -->
    <main class="flex-1 flex flex-col min-h-screen bg-[#f1f5f9] overflow-x-hidden">

        <!-- Top Navigation Bar -->
        <header class="h-16 bg-white border-b border-slate-200/90 px-8 flex items-center justify-between sticky top-0 z-10">
            <!-- Page Title -->
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                @hasSection('judul')
                    @yield('judul')
                @elseif (View::hasSection('page_title'))
                    @yield('page_title')
                @else
                    @yield('title', 'Dashboard')
                @endif
            </h2>

            <!-- User Profile Chip -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-slate-600 font-bold text-xs uppercase">
                    {{ substr(auth()->user()->nama, 0, 1) }}
                </div>
                <span class="text-sm font-medium text-slate-700">
                    {{ auth()->user()->nama }} ({{ auth()->user()->role->nama_peran ?? 'Admin' }})
                </span>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-8 pt-4">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Main Content -->
        <div class="p-8 max-w-7xl w-full">
            @hasSection('content')
                @yield('content')
            @else
                @yield('konten')
            @endif
        </div>

    </main>

    @stack('scripts')
    @yield('skrip')
</body>
</html>
