<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — InfraCheck</title>
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
    @include('partials.sidebar', ['active' => 'profile'])

    <!-- ==================== MAIN CONTENT AREA ==================== -->
    <main class="flex-1 flex flex-col min-h-screen bg-[#f1f5f9] overflow-x-hidden">

        <!-- Top Navigation Bar -->
        <header class="h-16 bg-white border-b border-slate-200/90 px-8 flex items-center justify-between sticky top-0 z-10">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Profil Saya</h2>

            <a
                href="{{ route('profile.index') }}"
                class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-slate-100"
                title="Buka profil saya"
            >
                <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-slate-600 font-bold text-xs uppercase">
                    {{ substr($user->nama, 0, 1) }}
                </div>
                <span class="text-sm font-medium text-slate-700">
                    {{ $user->nama }} ({{ $user->role->nama_peran ?? 'Tanpa Role' }})
                </span>
            </a>
        </header>

        <!-- Main Body -->
        <div class="p-8 max-w-5xl w-full">

            <!-- Flash Message -->
            @if (session('status'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-xs text-red-600">
                    <p class="mb-1 font-bold">Periksa kembali data berikut:</p>
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Ringkasan Akun -->
            <div class="mb-6 rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-base font-bold uppercase text-white">
                            {{ substr($user->nama, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $user->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-block rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-700">
                            {{ $user->role->nama_peran ?? 'Tanpa Role' }}
                        </span>
                        <span class="inline-block rounded-full px-3 py-1 text-[11px] font-semibold {{ $user->akun_aktif ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-red-200 bg-red-50 text-red-700' }}">
                            {{ $user->akun_aktif ? 'Akun Aktif' : 'Akun Nonaktif' }}
                        </span>
                    </div>
                </div>
                <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                    Role dan status akun hanya dapat diubah oleh Admin melalui halaman
                    <span class="font-semibold text-slate-600">Kelola Pengguna</span>.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <!-- ==================== DATA PROFIL ==================== -->
                <div class="lg:col-span-2 rounded-xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="text-base font-bold text-slate-900">Data Profil</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Perbarui informasi data diri yang tampil pada sistem.
                        </p>
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <!-- Nama -->
                            <div>
                                <label for="nama" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                    Nama <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama"
                                    name="nama"
                                    value="{{ old('nama', $user->nama) }}"
                                    required
                                    maxlength="255"
                                    placeholder="Nama lengkap"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('nama')
                                    <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    maxlength="255"
                                    placeholder="nama@infracheck.ac.id"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('email')
                                    <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- NIM / NIP -->
                            <div>
                                <label for="nim_nip" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                    NIM / NIP
                                </label>
                                <input
                                    type="text"
                                    id="nim_nip"
                                    name="nim_nip"
                                    value="{{ old('nim_nip', $user->nim_nip) }}"
                                    maxlength="255"
                                    placeholder="Opsional, contoh: 1202220001"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('nim_nip')
                                    <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Unit -->
                            <div>
                                <label for="unit" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                    Unit
                                </label>
                                <input
                                    type="text"
                                    id="unit"
                                    name="unit"
                                    value="{{ old('unit', $user->unit) }}"
                                    maxlength="255"
                                    placeholder="Contoh: Sistem Informasi"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('unit')
                                    <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Kontak -->
                            <div class="sm:col-span-2">
                                <label for="kontak" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                    Kontak
                                </label>
                                <input
                                    type="text"
                                    id="kontak"
                                    name="kontak"
                                    value="{{ old('kontak', $user->kontak) }}"
                                    maxlength="255"
                                    placeholder="Nomor telepon / WhatsApp"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('kontak')
                                    <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end border-t border-slate-100 pt-5">
                            <button
                                type="submit"
                                class="rounded-lg bg-[#2563eb] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                            >
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ==================== UBAH PASSWORD ==================== -->
                <div class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="text-base font-bold text-slate-900">Ubah Password</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Gunakan password lama untuk memverifikasi perubahan.
                        </p>
                    </div>

                    <form action="{{ route('profile.password') }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <!-- Password Lama -->
                        <div>
                            <label for="current_password" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                Password Lama <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    autocomplete="current-password"
                                    required
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="current_password"
                                    data-label-show="Tampilkan password lama"
                                    data-label-hide="Sembunyikan password lama"
                                    aria-label="Tampilkan password lama"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                >
                                    <svg data-icon="eye" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg data-icon="eye-off" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('current_password')
                                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password Baru -->
                        <div>
                            <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                Password Baru <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="new-password"
                                    required
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="password"
                                    data-label-show="Tampilkan password baru"
                                    data-label-hide="Sembunyikan password baru"
                                    aria-label="Tampilkan password baru"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                >
                                    <svg data-icon="eye" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg data-icon="eye-off" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Minimal 8 karakter.</p>
                            @error('password')
                                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Konfirmasi Password Baru -->
                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">
                                Konfirmasi Password Baru <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autocomplete="new-password"
                                    required
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="password_confirmation"
                                    data-label-show="Tampilkan konfirmasi password baru"
                                    data-label-hide="Sembunyikan konfirmasi password baru"
                                    aria-label="Tampilkan konfirmasi password baru"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                >
                                    <svg data-icon="eye" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg data-icon="eye-off" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end border-t border-slate-100 pt-5">
                            <button
                                type="submit"
                                class="rounded-lg bg-[#2563eb] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                            >
                                Ubah Password
                            </button>
                        </div>
                    </form>
                </div>

            </div>

        </div>

    </main>

    <!-- ==================== SHOW / HIDE PASSWORD ==================== -->
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            const input = document.getElementById(button.dataset.passwordToggle);

            if (! input) {
                return;
            }

            const labelShow = button.dataset.labelShow || 'Tampilkan password';
            const labelHide = button.dataset.labelHide || 'Sembunyikan password';
            const eyeIcon = button.querySelector('[data-icon="eye"]');
            const eyeOffIcon = button.querySelector('[data-icon="eye-off"]');

            button.addEventListener('click', function () {
                const willShow = input.type === 'password';

                input.type = willShow ? 'text' : 'password';

                button.setAttribute('aria-pressed', String(willShow));
                button.setAttribute('aria-label', willShow ? labelHide : labelShow);

                if (eyeIcon && eyeOffIcon) {
                    eyeIcon.classList.toggle('hidden', willShow);
                    eyeOffIcon.classList.toggle('hidden', ! willShow);
                }
            });
        });
    </script>

</body>
</html>
