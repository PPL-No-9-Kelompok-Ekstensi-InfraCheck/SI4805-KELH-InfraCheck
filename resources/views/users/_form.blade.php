@php
    $editing = isset($user);
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-xs text-red-600">
        <p class="mb-1 font-bold">Periksa kembali data berikut:</p>
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $editing ? route('users.update', $user) : route('users.store') }}" method="POST" class="space-y-6">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

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
                value="{{ old('nama', $user->nama ?? '') }}"
                required
                maxlength="255"
                placeholder="Nama lengkap pengguna"
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
                value="{{ old('email', $user->email ?? '') }}"
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
                value="{{ old('nim_nip', $user->nim_nip ?? '') }}"
                maxlength="255"
                placeholder="Opsional, contoh: 1202220001"
                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            @error('nim_nip')
                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Role -->
        <div>
            <label for="id_peran" class="mb-1.5 block text-xs font-semibold text-slate-700">
                Role <span class="text-red-500">*</span>
            </label>
            <select
                id="id_peran"
                name="id_peran"
                required
                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Pilih role</option>
                @foreach ($roles as $role)
                    <option
                        value="{{ $role->id_peran }}"
                        @selected((int) old('id_peran', $user->id_peran ?? 0) === (int) $role->id_peran)
                    >
                        {{ $role->nama_peran }}
                    </option>
                @endforeach
            </select>
            @error('id_peran')
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
                value="{{ old('unit', $user->unit ?? '') }}"
                maxlength="255"
                placeholder="Contoh: Sistem Informasi"
                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            @error('unit')
                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Kontak -->
        <div>
            <label for="kontak" class="mb-1.5 block text-xs font-semibold text-slate-700">
                Kontak
            </label>
            <input
                type="text"
                id="kontak"
                name="kontak"
                value="{{ old('kontak', $user->kontak ?? '') }}"
                maxlength="255"
                placeholder="Nomor telepon / WhatsApp"
                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            @error('kontak')
                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-700">
                Password @unless ($editing)<span class="text-red-500">*</span>@endunless
            </label>
            <input
                type="password"
                id="password"
                name="password"
                value=""
                autocomplete="new-password"
                {{ $editing ? '' : 'required' }}
                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <p class="mt-1 text-[11px] text-slate-500">
                @if ($editing)
                    Kosongkan jika tidak ingin mengubah password. Password lama akan tetap digunakan.
                @else
                    Minimal 8 karakter.
                @endif
            </p>
            @error('password')
                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Konfirmasi Password -->
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">
                Konfirmasi Password @unless ($editing)<span class="text-red-500">*</span>@endunless
            </label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                value=""
                autocomplete="new-password"
                {{ $editing ? '' : 'required' }}
                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            @error('password_confirmation')
                <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status Akun -->
        <div class="sm:col-span-2">
            <label class="flex cursor-pointer select-none items-center gap-2">
                <input type="hidden" name="akun_aktif" value="0">
                <input
                    type="checkbox"
                    name="akun_aktif"
                    value="1"
                    @checked(old('akun_aktif', $user->akun_aktif ?? true))
                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                >
                <span class="text-xs font-semibold text-slate-700">Akun aktif</span>
            </label>
            <p class="mt-1 text-[11px] text-slate-500">
                Akun nonaktif tetap tersimpan di sistem, tetapi tidak dapat login.
            </p>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
        <a
            href="{{ route('users.index') }}"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50"
        >
            Batal
        </a>
        <button
            type="submit"
            class="rounded-lg bg-[#2563eb] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
        >
            {{ $editing ? 'Simpan Perubahan' : 'Tambah Pengguna' }}
        </button>
    </div>
</form>
