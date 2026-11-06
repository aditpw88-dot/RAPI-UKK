<x-guest-layout>
    <style>
        .login-theme-input {
            border: 1px solid #e6d3bc !important;
            background-color: #ffffff !important;
            color: #29211b !important;
        }

        .login-theme-input:focus {
            border-color: #9a5a24 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgb(154 90 36 / 20%) !important;
        }

        .login-theme-checkbox {
            accent-color: #7c451e;
        }

        .login-theme-link {
            color: #854d1e !important;
        }

        .login-theme-link:hover {
            color: #b45309 !important;
        }

        .login-theme-submit {
            display: block;
            width: 100%;
            border: 2px solid #3b2b1f !important;
            background-color: #3b2b1f !important;
            color: #ffffff !important;
            font-weight: 700;
        }

        .login-theme-submit:hover {
            border-color: #7c451e !important;
            background-color: #7c451e !important;
        }

        .login-theme-back {
            border-color: #e6d3bc !important;
            background-color: #ffffff !important;
            color: #493524 !important;
        }

        .login-theme-back:hover {
            background-color: #fbf2e6 !important;
        }
    </style>

    <!-- Header -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-stone-900">
            {{ __('Masuk ke Akun Anda') }}
        </h2>
        <p class="mt-2 text-sm text-stone-600">
            {{ __('Silakan masukkan kredensial Anda') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}" class="space-y-5" data-confirmation-exempt>
        @csrf

        <!-- Email Input -->
        <div>
            <input id="email" 
                   type="email" 
                   name="email"
                   class="login-theme-input w-full rounded-lg border border-amber-200 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition duration-150 focus:border-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-700/20"
                   placeholder="Email"
                   value="{{ old('email') }}"
                   required 
                   autofocus 
                   autocomplete="username" />
            @error('email')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password Input -->
        <div>
            <input id="password" 
                   type="password" 
                   name="password"
                   class="login-theme-input w-full rounded-lg border border-amber-200 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition duration-150 focus:border-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-700/20"
                   placeholder="Kata Sandi"
                   required 
                   autocomplete="current-password" />
            @error('password')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Show Password -->
        <div>
            <label for="show_password" class="inline-flex items-center cursor-pointer">
                <input id="show_password" 
                       type="checkbox" 
                       onclick="document.getElementById('password').type = this.checked ? 'text' : 'password'"
                       class="login-theme-checkbox h-4 w-4 cursor-pointer rounded border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-700/20">
                <span class="ms-2 text-xs font-medium text-stone-700">{{ __('Tampilkan Password') }}</span>
            </label>
        </div>

        <!-- Forgot Password -->
        <div class="flex items-center justify-end">
            @if (Route::has('password.request'))
                <a href="{{ route('password.reset', absolute: false) }}" class="login-theme-link text-xs font-medium text-amber-800 transition duration-150 hover:text-orange-700">
                    {{ __('Lupa Password?') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <button type="submit" class="login-theme-submit w-full rounded-lg border-2 border-amber-900 bg-amber-900 px-4 py-3 text-sm font-semibold text-white shadow-md transition duration-150 hover:border-amber-800 hover:bg-amber-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-amber-700 focus:ring-offset-2">
            {{ __('Masuk') }}
        </button>
    </form>

    <!-- Back to Calendar -->
    <a href="{{ route('home') }}" class="login-theme-back mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-amber-200 bg-white px-4 py-3 text-sm font-semibold text-stone-800 shadow-md transition duration-150 hover:bg-amber-50 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v13a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
        </svg>
        <span>{{ __('Kembali ke Kalender') }}</span>
    </a>
</x-guest-layout>
