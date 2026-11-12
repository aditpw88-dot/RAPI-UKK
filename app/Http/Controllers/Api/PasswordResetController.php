<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Email tidak terdaftar di sistem kami.',
        ]);

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
            $user->forceFill([
                'otp' => $otp,
                'otp_expires_at' => $hasActiveOtp ? $user->otp_expires_at : $now->copy()->addMinutes(10),
                'otp_resend_attempts' => $hasActiveOtp ? ((int) $user->otp_resend_attempts + 1) : 0,
                'otp_last_sent_at' => $now,
            ])->save();

            try {
                Mail::to($user->email)->send(new SendOtpMail($otp, $user->fullname));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim email OTP API: '.$e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return ['user' => $user, 'remaining' => 0, 'mail_error' => $e->getMessage()];
            }

            return ['user' => $user, 'remaining' => 0];
        });

        if ($result['remaining'] > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tunggu '.$result['remaining'].' detik sebelum meminta kode OTP berikutnya.',
            ], 429);
        }

        if (! empty($result['mail_error'])) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email OTP: ' . $result['mail_error'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP telah dikirim ke email Anda. (Periksa juga folder SPAM).',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        if ($user->otp === null || ! hash_equals((string) $user->otp, (string) $validated['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP salah.',
            ], 422);
        }

        if ($user->otp_expires_at === null || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diverifikasi.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        if ($user->otp === null
            || ! hash_equals((string) $user->otp, (string) $validated['otp'])
            || $user->otp_expires_at === null
            || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa. Silakan mulai ulang proses reset password.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'otp' => null,
            'otp_expires_at' => null,
            'otp_resend_attempts' => 0,
            'otp_last_sent_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login.',
        ]);
    }
}
