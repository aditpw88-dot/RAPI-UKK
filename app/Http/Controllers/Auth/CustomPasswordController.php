<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CustomPasswordController extends Controller
{
    public function showResetForm(Request $request): View
    {
        $remainingSeconds = 0;
        $hasActiveOtp = false;
        $step = $request->query('step', 'email');
        $email = $request->session()->get('password_reset_email');
        $verifiedEmail = $request->session()->get('password_reset_verified_email');

        if (! in_array($step, ['email', 'otp', 'password'], true)) {
            $step = 'email';
        }

        if (! is_string($email) || $email === '') {
            $step = 'email';
        } elseif ($step === 'password' && $verifiedEmail !== $email) {
            $step = 'otp';
        }

        if ($step !== 'email') {
            $user = User::where('email', $email)->first();
            $hasActiveOtp = $user !== null
                && $user->otp !== null
                && $user->otp_expires_at !== null
                && now()->lessThanOrEqualTo($user->otp_expires_at);

            if (! $hasActiveOtp) {
                $request->session()->forget(['password_reset_email', 'password_reset_verified_email']);

                return view('auth.reset-password', [
                    'step' => 'email',
                    'email' => null,
                    'remainingSeconds' => 0,
                    'hasActiveOtp' => false,
                ]);
            }

            if ($hasActiveOtp && $user->otp_last_sent_at !== null) {
                $waitSeconds = ((int) $user->otp_resend_attempts + 1) * 30;
                $availableAt = $user->otp_last_sent_at->copy()->addSeconds($waitSeconds);
                $remainingSeconds = max(0, (int) ceil(now()->diffInMilliseconds($availableAt, false) / 1000));
            }
        }

        return view('auth.reset-password', compact('step', 'email', 'remainingSeconds', 'hasActiveOtp'));
    }

    // 1. Mengirim OTP saat user submit email di halaman forgot-password
    public function sendOtp(Request $request)
    {
        $request->session()->forget(['password_reset_email', 'password_reset_verified_email']);

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak terdaftar di sistem kami.',
        ]);
        $request->session()->put('password_reset_email', $validated['email']);
        $request->session()->forget('password_reset_verified_email');

        $result = DB::transaction(function () use ($validated): array {
            $user = User::where('email', $validated['email'])->lockForUpdate()->firstOrFail();
            $now = now();
            $hasActiveOtp = $user->otp !== null
                && $user->otp_expires_at !== null
                && $now->lessThanOrEqualTo($user->otp_expires_at);

            if ($hasActiveOtp && $user->otp_last_sent_at !== null) {
                $waitSeconds = ((int) $user->otp_resend_attempts + 1) * 30;
                $availableAt = $user->otp_last_sent_at->copy()->addSeconds($waitSeconds);
                $remainingSeconds = max(0, (int) ceil($now->diffInMilliseconds($availableAt, false) / 1000));

                if ($remainingSeconds > 0) {
                    return ['user' => $user, 'remaining' => $remainingSeconds];
                }
            }

            $otp = (string) random_int(100000, 999999);
            $resendAttempts = $hasActiveOtp ? ((int) $user->otp_resend_attempts + 1) : 0;

            $user->forceFill([
                'otp' => $otp,
                'otp_expires_at' => $hasActiveOtp ? $user->otp_expires_at : $now->copy()->addMinutes(10),
                'otp_resend_attempts' => $resendAttempts,
                'otp_last_sent_at' => $now,
            ])->save();

            Mail::to($user->email)->send(new SendOtpMail($otp, $user->fullname));

            return ['user' => $user, 'remaining' => 0];
        });

        $user = $result['user'];

        if ($result['remaining'] > 0) {
            return redirect()->route('password.reset', ['step' => 'otp'])
                ->withErrors([
                    'otp_resend' => 'Tunggu '.$this->formatWait($result['remaining']).' sebelum meminta kode OTP berikutnya.',
                ]);
        }

        return redirect()->route('password.reset', ['step' => 'otp'])
            ->with('status', 'Kode OTP telah dikirim ke email Anda.');
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $email = $request->session()->get('password_reset_email');
        if (! is_string($email) || $email === '') {
            return redirect()->route('password.reset')
                ->withErrors(['email' => 'Masukkan email Anda untuk memulai reset password.']);
        }

        $user = User::where('email', $email)->first();
        if ($user === null) {
            $request->session()->forget(['password_reset_email', 'password_reset_verified_email']);

            return redirect()->route('password.reset')
                ->withErrors(['email' => 'Email tidak terdaftar di sistem kami.']);
        }

        if ($user->otp === null || ! hash_equals((string) $user->otp, (string) $validated['otp'])) {
            return redirect()->route('password.reset', ['step' => 'otp'])
                ->withErrors(['otp' => 'Kode OTP salah.']);
        }

        if ($user->otp_expires_at === null || now()->greaterThan($user->otp_expires_at)) {
            $request->session()->forget(['password_reset_email', 'password_reset_verified_email']);

            return redirect()->route('password.reset')
                ->withErrors(['email' => 'Kode OTP sudah kadaluwarsa. Silakan masukkan email dan minta kode baru.']);
        }

        $request->session()->put('password_reset_verified_email', $email);

        return redirect()->route('password.reset', ['step' => 'password'])
            ->with('status', 'Email berhasil diverifikasi. Silakan buat password baru.');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $email = $request->session()->get('password_reset_email');
        if (! is_string($email) || $email === '' || $request->session()->get('password_reset_verified_email') !== $email) {
            return redirect()->route('password.reset', ['step' => 'otp'])
                ->withErrors(['otp' => 'Verifikasi kode OTP terlebih dahulu sebelum membuat password baru.']);
        }

        $user = User::where('email', $email)->first();
        if ($user === null || $user->otp === null || $user->otp_expires_at === null || now()->greaterThan($user->otp_expires_at)) {
            $request->session()->forget(['password_reset_email', 'password_reset_verified_email']);

            return redirect()->route('password.reset')
                ->withErrors(['email' => 'Kode OTP sudah tidak berlaku. Silakan mulai ulang proses reset password.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
            'otp_expires_at' => null,
            'otp_resend_attempts' => 0,
            'otp_last_sent_at' => null,
        ]);
        $request->session()->forget(['password_reset_email', 'password_reset_verified_email']);

        return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan login.');
    }

    private function formatWait(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' detik';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return $remainingSeconds === 0
            ? $minutes.' menit'
            : $minutes.' menit '.$remainingSeconds.' detik';
    }
}
