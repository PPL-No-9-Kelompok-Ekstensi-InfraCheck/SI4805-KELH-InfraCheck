<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — InfraCheck</title>
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

    <!-- ==================== SIDEBAR ==================== -->
    <aside class="w-64 bg-[#1e40af] text-white shrink-0 min-h-screen flex flex-col justify-between select-none">
        <div class="p-5">
            <!-- Sidebar Brand Header -->
            <div class="mb-7 pl-1">
                <h1 class="text-2xl font-bold tracking-tight text-white">InfraCheck</h1>
                <p class="text-xs text-blue-200/90 mt-0.5">
                    Masuk sebagai {{ $user->role->nama_peran ?? 'Admin' }}
                </p>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1">
                <!-- Dashboard (Active) -->
                <a href="{{ route('dashboard') }}" class="flex items-center px-3.5 py-2.5 rounded-lg bg-blue-600/70 text-white font-medium text-sm transition-colors shadow-sm">
                    Dashboard
                </a>

                <!-- SECTION: MASTER DATA -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        MASTER DATA
                    </p>
                </div>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Gedung
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Ruangan
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Kategori Infrastruktur
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Jenis Perangkat
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Perangkat
                </a>

                <!-- SECTION: LAPORAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        LAPORAN
                    </p>
                </div>
                <a href="{{ route('admin.verification.index') }}" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Verifikasi Laporan
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Prioritas Penanganan
                </a>
                <a href="{{ route('admin.assignment.create') }}" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Penugasan Teknisi
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Jadwal Perbaikan
                </a>

                <!-- SECTION: PEMANTAUAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        PEMANTAUAN
                    </p>
                </div>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Status Laporan
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Riwayat Penanganan
                </a>

                <!-- SECTION: PENGATURAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        PENGATURAN
                    </p>
                </div>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Pengguna
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Hak Akses
                </a>

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
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Dashboard</h2>

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

        <!-- Main Dashboard Body -->
        <div class="p-8 max-w-7xl w-full">
            
            <!-- 4 Top Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-7">
                <!-- Card 1: Laporan Masuk Hari Ini -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#2563eb]">
                    <p class="text-xs font-semibold text-slate-500">Laporan masuk hari ini</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $todayReportsCount }}</p>
                </div>

                <!-- Card 2: Menunggu Verifikasi -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#475569]">
                    <p class="text-xs font-semibold text-slate-500">Menunggu verifikasi</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $waitingVerificationCount }}</p>
                </div>

                <!-- Card 3: Dalam Perbaikan -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#f59e0b]">
                    <p class="text-xs font-semibold text-slate-500">Dalam perbaikan</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $inRepairCount }}</p>
                </div>

                <!-- Card 4: Selesai Bulan Ini -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#10b981]">
                    <p class="text-xs font-semibold text-slate-500">Selesai bulan ini</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $doneThisMonthCount }}</p>
                </div>
            </div>

            <!-- Lower 2 Columns Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

                <!-- LEFT COLUMN: Laporan Terbaru (2 spans) -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                    <h3 class="text-base font-bold text-slate-900 mb-5">Laporan Terbaru</h3>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                                    <th class="pb-3 font-semibold">NO. LAPORAN</th>
                                    <th class="pb-3 font-semibold">PERANGKAT</th>
                                    <th class="pb-3 font-semibold">LOKASI</th>
                                    <th class="pb-3 font-semibold">STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($recentReports as $report)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3.5 font-medium text-slate-800">{{ $report['nomor'] }}</td>
                                        <td class="py-3.5 text-slate-800">{{ $report['perangkat'] }}</td>
                                        <td class="py-3.5 text-slate-600">{{ $report['lokasi'] }}</td>
                                        <td class="py-3.5">
                                            <span class="inline-block px-3 py-0.5 rounded-full text-[11px] font-semibold {{ $report['badge_class'] }}">
                                                {{ $report['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Link -->
                    <div class="mt-6 pt-2">
                        <a href="#" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                            Lihat semua laporan &rarr;
                        </a>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Charts & Top Damaged (1 span) -->
                <div class="space-y-7">
                    
                    <!-- Card: Laporan per Gedung -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                        <h3 class="text-sm font-bold text-slate-900 mb-5">Laporan per Gedung (Okt 2026)</h3>
                        <div class="space-y-4">
                            @foreach ($buildingStats as $building)
                                <div class="flex items-center justify-between gap-4 text-xs">
                                    <span class="w-28 text-slate-600 truncate">{{ $building['nama'] }}</span>
                                    <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-[#2563eb] h-2.5 rounded-full" style="width: {{ $building['width'] }};"></div>
                                    </div>
                                    <span class="w-5 text-right font-bold text-slate-800">{{ $building['jumlah'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Card: Perangkat Sering Rusak -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                        <h3 class="text-sm font-bold text-slate-900 mb-5">Perangkat Sering Rusak</h3>
                        <div class="space-y-3.5">
                            @foreach ($frequentDamagedDevices as $device)
                                <div class="flex items-center justify-between text-xs py-1">
                                    <span class="text-slate-700">{{ $device['nama'] }}</span>
                                    <span class="font-bold text-slate-900">{{ $device['jumlah'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </main>

</body>
</html>
