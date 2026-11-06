<?php

namespace App\Support;

class WhatsAppLink
{
    public static function make(?string $phoneNumber, string $message): ?string
    {
        if (! $phoneNumber) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $phoneNumber);

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return null;
        }

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }
}
