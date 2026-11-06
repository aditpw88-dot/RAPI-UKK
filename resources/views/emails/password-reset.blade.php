<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Atur Ulang Kata Sandi RAPI</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px;background-color:#111827;">
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width:42px;height:42px;border-radius:12px;background-color:#ffffff;color:#111827;text-align:center;vertical-align:middle;font-size:20px;font-weight:bold;">R</td>
                                    <td style="padding-left:12px;color:#ffffff;">
                                        <div style="font-size:19px;font-weight:bold;letter-spacing:1px;">RAPI</div>
                                        <div style="margin-top:3px;color:#cbd5e1;font-size:12px;">Sistem Informasi Sekolah</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 28px;">
                            <div style="font-size:34px;line-height:1;">🔐</div>
                            <h1 style="margin:18px 0 12px;color:#0f172a;font-size:24px;line-height:1.3;">Atur ulang kata sandi</h1>
                            <p style="margin:0 0 16px;color:#334155;font-size:15px;line-height:1.7;">Halo {{ $name }},</p>
                            <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.7;">
                                Kami menerima permintaan untuk mengatur ulang kata sandi akun RAPI Anda.
                                Klik tombol di bawah ini untuk membuat kata sandi baru.
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="border-radius:8px;background-color:#1e293b;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 26px;border:1px solid #1e293b;border-radius:8px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;letter-spacing:.3px;">Atur Ulang Kata Sandi</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 8px;color:#64748b;font-size:13px;line-height:1.6;">
                                Demi keamanan, tautan ini berlaku selama {{ $expirationMinutes }} menit dan hanya dapat digunakan satu kali.
                            </p>
                            <p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">
                                Jika tombol tidak berfungsi, salin dan buka tautan berikut di browser:
                            </p>
                            <p style="margin:8px 0 0;word-break:break-all;font-size:12px;line-height:1.6;">
                                <a href="{{ $resetUrl }}" style="color:#2563eb;">{{ $resetUrl }}</a>
                            </p>
                            <div style="margin-top:24px;padding:14px 16px;border:1px solid #dbeafe;border-radius:8px;background-color:#eff6ff;color:#1e3a8a;font-size:13px;line-height:1.6;">
                                💡 Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini. Kata sandi Anda tidak akan berubah.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #e2e8f0;background-color:#f8fafc;color:#64748b;font-size:12px;line-height:1.6;text-align:center;">
                            Email otomatis dari <strong style="color:#334155;">RAPI-Sekolah</strong>.<br>
                            Mohon jangan membalas email ini.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
