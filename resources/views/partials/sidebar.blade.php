@php
    $sidebarUser = auth()->user();
    $activeNav = $active ?? '';
    $can = fn (string $code): bool => $sidebarUser !== null && $sidebarUser->hasPermission($code);
    $canAny = fn (array $codes): bool => $sidebarUser !== null && $sidebarUser->hasAnyPermission($codes);
    $linkClass = 'flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors';
    $activeClass = 'flex items-center px-3.5 py-1.5 rounded-lg bg-blue-600/70 text-white font-medium text-sm transition-colors shadow-sm';
    $showMasterData = $canAny(['buildings.view', 'rooms.view', 'categories.view', 'device-types.view', 'devices.view']);
    $showReports = $canAny(['reports.view', 'reports.create', 'report-verification.view', 'report-priorities.view', 'assignments.view', 'schedules.view']);
    $showMonitoring = $canAny(['report-statuses.view', 'report-histories.view']);
    $showSettings = $can('users.view') || $sidebarUser?->isAdmin();
@endphp

<!-- ==================== SIDEBAR ==================== -->
<aside class="w-64 bg-[#1e40af] text-white shrink-0 min-h-screen flex flex-col justify-between select-none">
    <div class="p-5">
        <!-- Sidebar Brand Header -->
        <div class="mb-7 pl-1">
            <h1 class="text-2xl font-bold tracking-tight text-white">InfraCheck</h1>
            <p class="text-xs text-blue-200/90 mt-0.5">
                Masuk sebagai {{ $sidebarUser?->role?->nama_peran ?? 'Admin' }}
            </p>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="{{ $activeNav === 'dashboard' ? 'flex items-center px-3.5 py-2.5 rounded-lg bg-blue-600/70 text-white font-medium text-sm transition-colors shadow-sm' : 'flex items-center px-3.5 py-2.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors' }}">
                Dashboard
            </a>

            @if ($showMasterData)
                <!-- SECTION: MASTER DATA -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        MASTER DATA
                    </p>
                </div>
                @if ($can('buildings.view'))
                    <a href="#" class="{{ $linkClass }}">Gedung</a>
                @endif
                @if ($can('rooms.view'))
                    <a href="#" class="{{ $linkClass }}">Ruangan</a>
                @endif
                @if ($can('categories.view'))
                    <a href="#" class="{{ $linkClass }}">Kategori Infrastruktur</a>
                @endif
                @if ($can('device-types.view'))
                    <a href="#" class="{{ $linkClass }}">Jenis Perangkat</a>
                @endif
                @if ($can('devices.view'))
                    <a href="#" class="{{ $linkClass }}">Perangkat</a>
                @endif
            @endif

            @if ($showReports)
                <!-- SECTION: LAPORAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        LAPORAN
                    </p>
                </div>
                @if ($can('reports.view'))
                    <a href="#" data-menu="reports" class="{{ $linkClass }}">Laporan</a>
                @endif
                @if ($can('report-verification.view'))
                    <a href="#" class="{{ $linkClass }}">Verifikasi Laporan</a>
                @endif
                @if ($can('report-priorities.view'))
                    <a href="#" class="{{ $linkClass }}">Prioritas Penanganan</a>
                @endif
                @if ($can('assignments.view'))
                    <a href="#" class="{{ $linkClass }}">Penugasan Teknisi</a>
                @endif
                @if ($can('schedules.view'))
                    <a href="#" class="{{ $linkClass }}">Jadwal Perbaikan</a>
                @endif
            @endif

            @if ($showMonitoring)
                <!-- SECTION: PEMANTAUAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        PEMANTAUAN
                    </p>
                </div>
                @if ($can('report-statuses.view'))
                    <a href="#" class="{{ $linkClass }}">Status Laporan</a>
                @endif
                @if ($can('report-histories.view'))
                    <a href="#" class="{{ $linkClass }}">Riwayat Penanganan</a>
                @endif
            @endif

            @if ($showSettings)
                <!-- SECTION: PENGATURAN -->
                <div class="pt-5 pb-1">
                    <p class="text-[10px] font-bold text-blue-200/70 uppercase tracking-wider px-3.5">
                        PENGATURAN
                    </p>
                </div>
                @if ($can('users.view'))
                    <a href="{{ route('users.index') }}" data-menu="users" class="{{ $activeNav === 'users' ? $activeClass : $linkClass }}">
                        Pengguna
                    </a>
                @endif
                @if ($sidebarUser?->isAdmin())
                    <a href="{{ route('access.index') }}" data-menu="access" class="{{ $activeNav === 'access' ? $activeClass : $linkClass }}">
                        Hak Akses
                    </a>
                @endif
            @endif

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
