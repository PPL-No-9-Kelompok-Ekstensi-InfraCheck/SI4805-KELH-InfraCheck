<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — InfraCheck</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN for instant styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#1d4ed8',
                        accent: '#e11d48',
                        darkblue: '#1b3ea8',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1c42b8;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased">
    <!-- Login Card -->
    <div class="bg-white w-full max-w-[430px] rounded-2xl shadow-2xl p-8 sm:p-9 relative">
        <!-- Logo / Title -->
        <div class="mb-7">
            <h1 class="text-3xl font-extrabold text-[#1e40af] tracking-tight">InfraCheck</h1>
            <p class="text-slate-500 text-xs sm:text-[13px] mt-1.5 leading-relaxed">
                Sistem Monitoring dan Pelaporan Infrastruktur Teknologi Kampus
            </p>
        </div>

        <!-- Alert Error Message -->
        @if ($errors->any())
            <div class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-red-600 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div class="mb-5 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
                {{ session('status') }}
            </div>
        @endif

        <!-- Form Login -->
        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <!-- Email / NIM / NIP -->
            <div class="mb-4">
                <label for="login" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Email / NIM / NIP
                </label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    value="{{ old('login') }}" 
                    placeholder="contoh: 1202230001" 
                    required 
                    autofocus
                    class="w-full px-3.5 py-2.5 text-sm text-slate-800 bg-white border border-slate-300 rounded-lg placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                >
            </div>

            <!-- Password with Show/Hide Toggle -->
            <div class="mb-4">
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Password
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••" 
                        required 
                        class="w-full px-3.5 py-2.5 pr-10 text-sm text-slate-800 bg-white border border-slate-300 rounded-lg placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    >
                    <button 
                        type="button" 
                        id="togglePassword" 
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none"
                        aria-label="Tampilkan / Sembunyikan Password"
                    >
                        <!-- Eye Icon SVG -->
                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <!-- Eye Slash Icon (Hidden by default) -->
                        <svg id="eyeSlashIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between mt-3 mb-6">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input 
                        type="checkbox" 
                        name="remember" 
                        class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >
                    <span class="text-xs text-slate-600">Ingat saya</span>
                </label>
                <a href="#" class="text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                    Lupa password?
                </a>
            </div>

            <!-- Submit Button (Masuk) -->
            <button 
                type="submit" 
                class="w-full py-2.5 px-4 bg-[#e11d48] hover:bg-[#be123c] text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-150 active:scale-[0.99] text-sm tracking-wide text-center"
            >
                Masuk
            </button>
        </form>

        <!-- Footer -->
        <p class="text-[11px] text-slate-400 text-center mt-6">
            Akun dibuat oleh Admin Unit IT
        </p>
    </div>

    <!-- Script Show/Hide Password -->
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeSlashIcon = document.getElementById('eyeSlashIcon');

        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            eyeIcon.classList.toggle('hidden', isPassword);
            eyeSlashIcon.classList.toggle('hidden', !isPassword);
        });
    </script>
</body>
</html>
