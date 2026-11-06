<?php

namespace Tests\Feature\Auth;

use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_request_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertRedirect(route('password.reset'));
    }

    public function test_reset_password_starts_with_the_email_step_only(): void
    {
        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee('Langkah 1 dari 3')
            ->assertSee('Email terdaftar')
            ->assertSee('Kirim Kode OTP')
            ->assertDontSee('id="otp"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('Password Baru');
    }

    public function test_otp_is_sent_to_the_user_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.reset', ['step' => 'otp']))
            ->assertSessionHas('status');

        Mail::assertSent(SendOtpMail::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->otp === $user->fresh()->otp);
        $this->assertNotNull($user->fresh()->otp_expires_at);
    }

    public function test_reset_page_shows_remaining_otp_resend_countdown(): void
    {
        $user = User::factory()->create([
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
            'otp_resend_attempts' => 0,
            'otp_last_sent_at' => now(),
        ]);

        $this->withSession(['password_reset_email' => $user->email])
            ->get(route('password.reset', ['step' => 'otp']))
            ->assertOk()
            ->assertSee('Langkah 2 dari 3')
            ->assertSee('data-resend-seconds="30"', false)
            ->assertSee('Kirim ulang dalam 00:30')
            ->assertSee('disabled', false);
    }

    public function test_reset_page_enables_otp_resend_button_after_cooldown(): void
    {
        $user = User::factory()->create([
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
            'otp_resend_attempts' => 0,
            'otp_last_sent_at' => now()->subSeconds(30),
        ]);

        $this->withSession(['password_reset_email' => $user->email])
            ->get(route('password.reset', ['step' => 'otp']))
            ->assertOk()
            ->assertSee('Langkah 2 dari 3')
            ->assertSee('data-resend-seconds="0"', false)
            ->assertSee('Kirim Ulang Kode OTP')
            ->assertDontSee('disabled="disabled"', false);
    }

    public function test_otp_resend_waits_thirty_seconds_and_then_increases_the_wait_by_thirty_seconds(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $start = now();
        $this->travelTo($start);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.reset', ['step' => 'otp']));
        Mail::assertSent(SendOtpMail::class, 1);

        $this->travel(29)->seconds();
        $this->withSession(['password_reset_email' => $user->email])
            ->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('otp_resend');
        Mail::assertSent(SendOtpMail::class, 1);

        $this->travel(1)->second();
        $this->withSession(['password_reset_email' => $user->email])
            ->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');
        Mail::assertSent(SendOtpMail::class, 2);

        $this->travel(59)->seconds();
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('otp_resend');
        Mail::assertSent(SendOtpMail::class, 2);

        $this->travel(1)->second();
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');
        Mail::assertSent(SendOtpMail::class, 3);

        $user->refresh();
        $this->assertSame(2, $user->otp_resend_attempts);
    }

    public function test_reset_password_screen_can_be_rendered_with_email(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $this->withSession(['password_reset_email' => $user->email])
            ->get(route('password.reset', ['step' => 'otp']))
            ->assertOk()
            ->assertSee('Langkah 2 dari 3')
            ->assertSee($user->email);
    }

    public function test_valid_otp_moves_to_a_separate_new_password_step(): void
    {
        $user = User::factory()->create([
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
            'otp_resend_attempts' => 2,
            'otp_last_sent_at' => now(),
        ]);

        $this->withSession(['password_reset_email' => $user->email])
            ->post(route('password.verify-otp'), [
            'otp' => '123456',
        ])->assertRedirect(route('password.reset', ['step' => 'password']))
            ->assertSessionHas('password_reset_verified_email', $user->email);

        $this->get(route('password.reset', ['step' => 'password']))
            ->assertOk()
            ->assertSee('Langkah 3 dari 3')
            ->assertSee('Password Baru')
            ->assertSee('Konfirmasi Password Baru')
            ->assertDontSee('Kode OTP');

        $this->post('/reset-password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertNull($user->otp);
        $this->assertNull($user->otp_expires_at);
        $this->assertSame(0, $user->otp_resend_attempts);
        $this->assertNull($user->otp_last_sent_at);
    }

    public function test_password_reset_rejects_an_invalid_otp(): void
    {
        $user = User::factory()->create([
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession(['password_reset_email' => $user->email])
            ->post(route('password.verify-otp'), [
                'otp' => '654321',
            ])
            ->assertSessionHasErrors('otp');

        $this->assertFalse(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_password_reset_rejects_an_expired_otp(): void
    {
        $user = User::factory()->create([
            'otp' => '123456',
            'otp_expires_at' => now()->subMinute(),
        ]);

        $this->withSession(['password_reset_email' => $user->email])
            ->post(route('password.verify-otp'), [
                'otp' => '123456',
            ])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHasErrors('email');
    }

    public function test_password_reset_cannot_skip_otp_verification(): void
    {
        $user = User::factory()->create([
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession(['password_reset_email' => $user->email])
            ->post('/reset-password', [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('password.reset', ['step' => 'otp']))
            ->assertSessionHasErrors('otp');

        $this->assertFalse(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_invalid_new_email_clears_previous_reset_session(): void
    {
        $user = User::factory()->create();

        $this->withSession([
            'password_reset_email' => $user->email,
            'password_reset_verified_email' => $user->email,
        ])->post('/forgot-password', ['email' => 'not-registered@example.com'])
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('password_reset_email')
            ->assertSessionMissing('password_reset_verified_email');
    }

    public function test_otp_email_uses_rapi_branding(): void
    {
        $mail = new SendOtpMail('123456', 'Adit Pratama');
        $html = $mail->render();

        $this->assertSame('🔐 Kode OTP Reset Password RAPI', $mail->envelope()->subject);
        $this->assertStringContainsString('Sistem RAPI', $html);
        $this->assertStringContainsString('Halo Adit Pratama', $html);
        $this->assertStringContainsString('123456', $html);
    }

    public function test_forgot_password_requests_are_rate_limited(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/forgot-password', ['email' => $user->email])
                ->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertTooManyRequests();
    }
}
