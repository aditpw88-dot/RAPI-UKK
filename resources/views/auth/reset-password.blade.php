<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 shadow-xs">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <p class="font-semibold text-red-800">Perhatian / Kendala:</p>
                    <ul class="mt-1 list-disc list-inside space-y-1 text-xs text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Pemulihan Akun · Langkah {{ ['email' => '1 dari 3', 'otp' => '2 dari 3', 'password' => '3 dari 3'][$step] }}</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">
            @if ($step === 'email')
                Reset Password
            @elseif ($step === 'otp')
                Verifikasi Email
            @else
                Buat Password Baru
            @endif
        </h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">
            @if ($step === 'email')
                Masukkan email yang terdaftar. Kami akan mengirimkan kode OTP untuk memverifikasi akun Anda.
            @elseif ($step === 'otp')
                Masukkan 6 digit kode OTP yang dikirim ke <strong>{{ $email }}</strong>. Kode berlaku selama 10 menit.
            @else
                Email <strong>{{ $email }}</strong> sudah diverifikasi. Buat password baru untuk akun Anda.
            @endif
        </p>
    </div>

    <div class="mb-6 grid grid-cols-3 gap-2" aria-label="Tahapan reset password">
        <div class="h-1.5 rounded-full {{ in_array($step, ['email', 'otp', 'password'], true) ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
        <div class="h-1.5 rounded-full {{ in_array($step, ['otp', 'password'], true) ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
        <div class="h-1.5 rounded-full {{ $step === 'password' ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
    </div>

    @if ($step === 'email')
        <form method="POST" action="{{ route('password.email') }}" data-confirmation-exempt>
            @csrf
            <div>
                <x-input-label for="email" :value="__('Email terdaftar')" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" placeholder="nama@email.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="mt-6 flex items-center justify-between gap-3">
                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Kembali ke Login</a>
                <x-primary-button>{{ __('Kirim Kode OTP') }}</x-primary-button>
            </div>
        </form>
    @elseif ($step === 'otp')
        <form method="POST" action="{{ route('password.verify-otp') }}" data-confirmation-exempt>
            @csrf
            <div>
                <x-input-label for="otp" :value="__('Kode OTP')" />
                <x-text-input id="otp" class="mt-1 block w-full text-center text-lg tracking-[0.4em]" type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus placeholder="000000" />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>
            <div class="mt-6 flex items-center justify-between gap-3">
                <a href="{{ route('password.reset') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Ganti Email</a>
                <x-primary-button>{{ __('Verifikasi OTP') }}</x-primary-button>
            </div>
        </form>

        <div class="mt-6 border-t border-gray-200 pt-4">
            <p class="mb-3 text-sm text-gray-600">Belum menerima kode?</p>
            <x-input-error :messages="$errors->get('otp_resend')" class="mb-3" />
            <form method="POST" action="{{ route('password.email') }}" data-confirmation-exempt>
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button
                    id="otp-resend-button"
                    type="submit"
                    data-resend-seconds="{{ $remainingSeconds }}"
                    data-has-active-otp="{{ $hasActiveOtp ? 'true' : 'false' }}"
                    {{ $remainingSeconds > 0 ? 'disabled' : '' }}
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    @if ($remainingSeconds > 0)
                        {{ __('Kirim ulang dalam') }} {{ sprintf('%02d:%02d', intdiv($remainingSeconds, 60), $remainingSeconds % 60) }}
                    @else
                        {{ __('Kirim Ulang Kode OTP') }}
                    @endif
                </button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('password.store') }}" data-confirmation-exempt>
            @csrf
            <div>
                <x-input-label for="password" :value="__('Password Baru')" />
                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" autofocus />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
            <div class="mt-6 flex items-center justify-end gap-3">
                <x-primary-button>{{ __('Simpan Password Baru') }}</x-primary-button>
            </div>
        </form>
    @endif

    @if ($step === 'otp' && $remainingSeconds > 0)
        <script>
            (() => {
                const button = document.getElementById('otp-resend-button');
                if (!button) return;

                let remaining = Number(button.dataset.resendSeconds);
                const updateCountdown = () => {
                    if (remaining <= 0) {
                        button.disabled = false;
                        button.textContent = 'Kirim Ulang Kode OTP';
                        return;
                    }

                    button.textContent = `Kirim ulang dalam ${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`;
                    button.disabled = true;
                    remaining -= 1;
                    window.setTimeout(updateCountdown, 1000);
                };

                updateCountdown();
            })();
        </script>
    @endif
</x-guest-layout>
