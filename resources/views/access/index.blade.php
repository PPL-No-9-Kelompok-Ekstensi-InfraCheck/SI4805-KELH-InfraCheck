<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hak Akses — InfraCheck</title>
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

    @include('partials.sidebar', ['active' => 'access'])

    <!-- ==================== MAIN CONTENT AREA ==================== -->
    <main class="flex-1 flex flex-col min-h-screen bg-[#f1f5f9] overflow-x-hidden">

        <!-- Top Navigation Bar -->
        <header class="h-16 bg-white border-b border-slate-200/90 px-8 flex items-center justify-between sticky top-0 z-10">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Hak Akses</h2>

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
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Matriks Hak Akses</h3>
                        <p class="mt-1 max-w-3xl text-xs leading-relaxed text-slate-500">
                            Centang aksi yang boleh dilakukan setiap role. Perubahan berlaku langsung setelah disimpan
                            dan akan dipakai pada pemeriksaan hak akses berikutnya.
                        </p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-relaxed text-amber-700">
                        Hak akses <strong>Lihat Dashboard</strong> wajib dimiliki setiap role. Hak akses modul
                        <strong>Hak Akses</strong> hanya dapat dimiliki role Admin. Keduanya tidak dapat diubah
                        dari halaman ini.
                    </div>
                </div>

                <form action="{{ route('access.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="pb-3 pr-3 font-semibold">Modul / Hak Akses</th>
                                    @foreach ($roles as $role)
                                        <th class="px-3 pb-3 text-center font-semibold">{{ $role->nama_peran }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (config('permissions.groups') as $groupName => $modules)
                                    <tr class="bg-slate-100/80">
                                        <td colspan="{{ 1 + $roles->count() }}" class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            {{ $groupName }}
                                        </td>
                                    </tr>

                                    @foreach ($modules as $module)
                                        <tr class="bg-slate-50">
                                            <td colspan="{{ 1 + $roles->count() }}" class="px-3 py-2 text-[11px] font-semibold text-slate-700">
                                                {{ $module['nama'] }}
                                            </td>
                                        </tr>

                                        @foreach ($module['aksi'] as $action)
                                            @php
                                                $code = $module['kode'].'.'.$action;
                                                $permission = $permissions->get($code);
                                            @endphp
                                            <tr class="border-b border-slate-100 transition-colors hover:bg-slate-50/70">
                                                <td class="py-2.5 pl-6 pr-3 text-slate-600">
                                                    {{ config('permissions.actions')[$action] }}
                                                </td>
                                                @foreach ($roles as $role)
                                                    @php
                                                        $roleId = (int) $role->id_peran;
                                                        $permissionId = $permission?->id_hak_akses;
                                                        $checked = $permissionId !== null
                                                            && in_array((int) $permissionId, $selected[$roleId] ?? [], true);
                                                        $locked = $permissionId !== null
                                                            && in_array($code, $lockedCells[$roleId] ?? [], true);
                                                        $blocked = $permissionId !== null
                                                            && in_array($code, $blockedCells[$roleId] ?? [], true);
                                                    @endphp
                                                    <td class="px-3 py-2.5 text-center align-middle">
                                                        @if ($permission === null)
                                                            <span class="text-slate-300">&mdash;</span>
                                                        @elseif ($locked)
                                                            <input
                                                                type="hidden"
                                                                name="permissions[{{ $roleId }}][]"
                                                                value="{{ $permissionId }}"
                                                            >
                                                            <input
                                                                type="checkbox"
                                                                checked
                                                                disabled
                                                                class="h-4 w-4 cursor-not-allowed rounded border-slate-300 text-blue-600"
                                                                title="Wajib dimiliki role ini"
                                                            >
                                                        @elseif ($blocked)
                                                            <input
                                                                type="checkbox"
                                                                disabled
                                                                class="h-4 w-4 cursor-not-allowed rounded border-slate-300 text-slate-400"
                                                                title="Hanya dapat dimiliki role Admin"
                                                            >
                                                        @else
                                                            <input
                                                                type="checkbox"
                                                                name="permissions[{{ $roleId }}][]"
                                                                value="{{ $permissionId }}"
                                                                @checked($checked)
                                                                class="h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                                            >
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[11px] text-slate-500">
                            Menampilkan {{ $roles->count() }} role. Tanda &mdash; berarti aksi tersebut tidak tersedia untuk modulnya.
                        </p>
                        @if (auth()->user()?->hasPermission('access.update'))
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-[#2563eb] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                            >
                                Simpan Perubahan
                            </button>
                        @endif
                    </div>
                </form>
            </div>

        </div>

    </main>

</body>
</html>
