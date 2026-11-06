<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode OTP RAPI</title>
</head>
<body style="margin:0;padding:32px 12px;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px;background:#111827;color:#ffffff;">
                            <strong style="font-size:20px;letter-spacing:1px;">RAPI</strong>
                            <div style="margin-top:4px;color:#cbd5e1;font-size:12px;">Sistem Informasi Sekolah</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px;">
                            <div style="font-size:32px;">🔐</div>
                            <h1 style="margin:16px 0 12px;font-size:24px;">Kode reset password Anda</h1>
                            <p style="color:#475569;line-height:1.7;">Halo {{ $name ?: 'Pengguna RAPI' }}, masukkan kode berikut untuk melanjutkan reset password:</p>
                            <p style="margin:24px 0;padding:18px;text-align:center;border-radius:10px;background:#f1f5f9;color:#0f172a;font-size:32px;font-weight:bold;letter-spacing:8px;">{{ $otp }}</p>
                            <p style="color:#64748b;font-size:14px;line-height:1.7;">Kode berlaku selama 10 menit dan hanya dapat digunakan satu kali. Jangan bagikan kode ini kepada siapa pun.</p>
                            <p style="margin-bottom:0;color:#64748b;font-size:13px;">Jika Anda tidak meminta reset password, abaikan email ini.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:12px;text-align:center;">
                            Email otomatis dari <strong style="color:#334155;">Sistem RAPI</strong>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
