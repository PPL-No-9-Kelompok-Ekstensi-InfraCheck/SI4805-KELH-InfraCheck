<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna — InfraCheck</title>
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
                    Masuk sebagai {{ auth()->user()->role->nama_peran ?? 'Admin' }}
                </p>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="flex items-center px-3.5 py-2.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
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
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Verifikasi Laporan
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
                    Prioritas Penanganan
                </a>
                <a href="#" class="flex items-center px-3.5 py-1.5 rounded-lg text-blue-100 hover:bg-blue-700/60 hover:text-white text-sm transition-colors">
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
                <a href="{{ route('users.index') }}" class="flex items-center px-3.5 py-1.5 rounded-lg bg-blue-600/70 text-white font-medium text-sm transition-colors shadow-sm">
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
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Kelola Pengguna</h2>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-slate-600 font-bold text-xs uppercase">
                    {{ substr(auth()->user()->nama, 0, 1) }}
                </div>
                <span class="text-sm font-medium text-slate-700">
                    {{ auth()->user()->nama }} ({{ auth()->user()->role->nama_peran ?? 'Admin' }})
                </span>
            </div>
        </header>

        <!-- Main Body -->
        <div class="p-8 max-w-7xl w-full">

            <!-- Flash & Error Messages -->
            @if (session('status'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3 text-xs font-medium text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <!-- Card Header -->
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Daftar Pengguna</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Kelola akun pengguna InfraCheck beserta role dan status aktivasinya.
                        </p>
                    </div>
                    <a
                        href="{{ route('users.create') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-[#2563eb] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                    >
                        + Tambah Pengguna
                    </a>
                </div>

                <!-- Search -->
                <form action="{{ route('users.index') }}" method="GET" class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Cari nama, email, atau NIM/NIP..."
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="rounded-lg bg-[#1e40af] px-4 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-blue-800"
                        >
                            Cari
                        </button>
                        @if ($search !== '')
                            <a
                                href="{{ route('users.index') }}"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50"
                            >
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 font-bold uppercase tracking-wider text-slate-400">
                                <th class="pb-3 pr-3 font-semibold">NO.</th>
                                <th class="pb-3 pr-3 font-semibold">NAMA</th>
                                <th class="pb-3 pr-3 font-semibold">EMAIL</th>
                                <th class="pb-3 pr-3 font-semibold">NIM / NIP</th>
                                <th class="pb-3 pr-3 font-semibold">UNIT</th>
                                <th class="pb-3 pr-3 font-semibold">ROLE</th>
                                <th class="pb-3 pr-3 font-semibold">STATUS</th>
                                <th class="pb-3 font-semibold">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($users as $user)
                                <tr class="transition-colors hover:bg-slate-50/70">
                                    <td class="py-3.5 pr-3 font-medium text-slate-500">
                                        {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="py-3.5 pr-3 font-medium text-slate-800">
                                        {{ $user->nama }}
                                    </td>
                                    <td class="py-3.5 pr-3 text-slate-600">
                                        {{ $user->email }}
                                    </td>
                                    <td class="py-3.5 pr-3 text-slate-600">
                                        {{ $user->nim_nip ?: '-' }}
                                    </td>
                                    <td class="py-3.5 pr-3 text-slate-600">
                                        {{ $user->unit ?: '-' }}
                                    </td>
                                    <td class="py-3.5 pr-3">
                                        <span class="inline-block rounded-full border border-blue-200 bg-blue-50 px-3 py-0.5 text-[11px] font-semibold text-blue-700">
                                            {{ $user->role->nama_peran ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 pr-3">
                                        @if ($user->akun_aktif)
                                            <span class="inline-block rounded-full border border-emerald-200 bg-emerald-100 px-3 py-0.5 text-[11px] font-semibold text-emerald-700">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-block rounded-full border border-slate-200 bg-slate-100 px-3 py-0.5 text-[11px] font-semibold text-slate-600">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5">
                                        <div class="flex items-center gap-2">
                                            <a
                                                href="{{ route('users.edit', $user) }}"
                                                class="rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 transition-colors hover:bg-blue-100"
                                            >
                                                Edit
                                            </a>

                                            @if ((int) $user->id_pengguna === (int) auth()->id())
                                                <span class="px-1 text-[11px] font-medium text-slate-400">Akun Anda</span>
                                            @else
                                                <form
                                                    action="{{ route('users.destroy', $user) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus pengguna ini secara permanen? Akun akan dihapus dari database dan tidak dapat login lagi. Pengguna yang masih memiliki laporan, penugasan, komentar, atau riwayat terkait tidak dapat dihapus.');"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-600 transition-colors hover:bg-red-100"
                                                    >
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-xs text-slate-500">
                                        @if ($search !== '')
                                            Tidak ada pengguna yang cocok dengan pencarian "{{ $search }}".
                                        @else
                                            Belum ada data pengguna.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[11px] text-slate-500">
                        Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} pengguna
                    </p>
                    <div>
                        {{ $users->links() }}
                    </div>
                </div>
            </div>

        </div>

    </main>

</body>
</html>
