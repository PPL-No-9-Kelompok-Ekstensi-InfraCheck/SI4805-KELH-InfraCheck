<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna — InfraCheck</title>
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
    @include('partials.sidebar', ['active' => 'users'])

    <!-- ==================== MAIN CONTENT AREA ==================== -->
    <main class="flex-1 flex flex-col min-h-screen bg-[#f1f5f9] overflow-x-hidden">

        <!-- Top Navigation Bar -->
        <header class="h-16 bg-white border-b border-slate-200/90 px-8 flex items-center justify-between sticky top-0 z-10">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Edit Pengguna</h2>

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
        <div class="p-8 max-w-4xl w-full">

            <div class="mb-5">
                <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 transition-colors hover:text-blue-700">
                    &larr; Kembali ke daftar pengguna
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                <div class="mb-6">
                    <h3 class="text-base font-bold text-slate-900">Edit Data Pengguna</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $user->nama }} &middot; {{ $user->email }}
                    </p>
                </div>

                @include('users._form', ['user' => $user, 'roles' => $roles])
            </div>

        </div>

    </main>

</body>
</html>
