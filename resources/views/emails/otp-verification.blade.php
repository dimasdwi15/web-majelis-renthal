<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Kode Verifikasi — Majelis Rental</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f4f4ef;font-family:'Inter',system-ui,-apple-system,sans-serif;-webkit-font-smoothing:antialiased;">

    {{-- ═══ Outer wrapper ═══ --}}
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4ef;padding:48px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="520"
                       style="max-width:520px;width:100%;background-color:#faf9f5;border-radius:4px;overflow:hidden;">

                    {{-- ── Accent strip ── --}}
                    <tr>
                        <td style="padding:0;line-height:0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td width="45%" style="height:3px;background-color:#655e44;"></td>
                                    <td width="30%" style="height:3px;background-color:#4a4530;"></td>
                                    <td width="25%" style="height:3px;background-color:#2c2416;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── Command Header ── --}}
                    <tr>
                        <td style="background-color:#251D1D;padding:24px 32px 22px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td valign="middle">
                                        <p style="font-size:13px;font-weight:800;color:#f2e8c6;letter-spacing:0.18em;margin:0 0 3px;text-transform:uppercase;">
                                            MAJELIS RENTAL
                                        </p>
                                        <p style="font-size:9px;font-weight:600;color:rgba(242,232,198,0.3);letter-spacing:0.26em;margin:0;text-transform:uppercase;">
                                            SISTEM MANAJEMEN PENYEWAAN
                                        </p>
                                    </td>
                                    <td align="right" valign="middle">
                                        <p style="font-size:9px;font-weight:700;color:rgba(242,232,198,0.15);letter-spacing:0.22em;margin:0;text-transform:uppercase;">
                                            EMAIL VERIFICATION
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── Body ── --}}
                    <tr>
                        <td style="padding:36px 36px 0;">

                            {{-- Section label --}}
                            <p style="font-size:10px;font-weight:700;color:#655e44;letter-spacing:0.16em;text-transform:uppercase;margin:0 0 8px;">
                                Verifikasi Akun
                            </p>

                            {{-- Headline --}}
                            <h1 style="font-size:26px;font-weight:900;color:#251D1D;letter-spacing:-0.02em;margin:0 0 16px;line-height:1.15;">
                                Kode OTP Anda Sudah Siap
                            </h1>

                            {{-- Body text --}}
                            <p style="font-size:14px;color:#5c5852;line-height:1.8;margin:0 0 32px;">
                                Selamat datang di <strong style="color:#251D1D;">Majelis Rental</strong>.
                                Gunakan kode 6 digit berikut untuk memverifikasi alamat email
                                <strong style="color:#251D1D;">{{ $otp->email }}</strong>.
                            </p>

                            {{-- ── OTP Panel (machined block) ── --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                   style="margin-bottom:10px;">
                                <tr>
                                    {{-- Left accent rail --}}
                                    <td style="width:4px;background-color:#655e44;border-radius:4px 0 0 4px;"></td>

                                    <td style="background-color:#f4f4ef;padding:28px 32px;text-align:center;border-radius:0 4px 4px 0;">
                                        <p style="font-size:10px;font-weight:700;color:rgba(37,29,29,0.3);letter-spacing:0.24em;text-transform:uppercase;margin:0 0 18px;">
                                            KODE VERIFIKASI OTP
                                        </p>
                                        <p style="font-size:52px;font-weight:900;color:#251D1D;letter-spacing:0.4em;margin:0 0 0 0.4em;line-height:1;font-variant-numeric:tabular-nums;">
                                            {{ $otp->otp }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Expiry --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                   style="margin-bottom:32px;">
                                <tr>
                                    <td align="center"
                                        style="padding:10px;background-color:#ffffff;border-radius:4px;">
                                        <p style="font-size:12px;color:rgba(37,29,29,0.4);margin:0;">
                                            Berlaku&nbsp;&nbsp;<strong style="color:#655e44;letter-spacing:0.02em;">10 MENIT</strong>
                                            &nbsp;·&nbsp;
                                            Kedaluwarsa:&nbsp;
                                            <strong style="color:#251D1D;">
                                                {{ $otp->expires_at->setTimezone('Asia/Jakarta')->format('H:i \W\I\B') }}
                                            </strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- ── Step guide ── --}}
                            <p style="font-size:10px;font-weight:700;color:rgba(37,29,29,0.3);letter-spacing:0.18em;text-transform:uppercase;margin:0 0 14px;">
                                CARA PENGGUNAAN
                            </p>

                            {{-- Step 1 --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                   style="margin-bottom:10px;">
                                <tr>
                                    <td valign="top" style="width:28px;">
                                        <div style="width:24px;height:24px;background-color:#655e44;border-radius:2px;text-align:center;line-height:24px;font-size:11px;font-weight:800;color:#f2e8c6;">1</div>
                                    </td>
                                    <td style="padding-left:10px;font-size:13px;color:#5c5852;line-height:1.6;padding-top:3px;">
                                        Buka halaman verifikasi di browser, lalu pilih tab
                                        <strong style="color:#251D1D;">"Via Kode OTP"</strong>
                                    </td>
                                </tr>
                            </table>

                            {{-- Step 2 --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                   style="margin-bottom:10px;">
                                <tr>
                                    <td valign="top" style="width:28px;">
                                        <div style="width:24px;height:24px;background-color:#655e44;border-radius:2px;text-align:center;line-height:24px;font-size:11px;font-weight:800;color:#f2e8c6;">2</div>
                                    </td>
                                    <td style="padding-left:10px;font-size:13px;color:#5c5852;line-height:1.6;padding-top:3px;">
                                        Ketik atau tempel kode
                                        <strong style="color:#251D1D;letter-spacing:0.05em;font-size:14px;">{{ $otp->otp }}</strong>
                                        pada kotak input yang tersedia
                                    </td>
                                </tr>
                            </table>

                            {{-- Step 3 --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                   style="margin-bottom:36px;">
                                <tr>
                                    <td valign="top" style="width:28px;">
                                        <div style="width:24px;height:24px;background-color:#655e44;border-radius:2px;text-align:center;line-height:24px;font-size:11px;font-weight:800;color:#f2e8c6;">3</div>
                                    </td>
                                    <td style="padding-left:10px;font-size:13px;color:#5c5852;line-height:1.6;padding-top:3px;">
                                        Klik tombol <strong style="color:#251D1D;">"Verifikasi OTP"</strong>
                                        — selesai, akun Anda aktif
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- ── Security notice ── --}}
                    <tr>
                        <td style="padding:0 36px 36px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                   style="background-color:#f4f4ef;border-radius:4px;">
                                <tr>
                                    {{-- Left accent, darker --}}
                                    <td style="width:3px;background-color:#4a4530;border-radius:4px 0 0 4px;opacity:0.5;"></td>
                                    <td style="padding:14px 18px;">
                                        <p style="font-size:12px;color:rgba(37,29,29,0.5);margin:0;line-height:1.75;">
                                            <strong style="color:#251D1D;">⚠ Keamanan:</strong>
                                            Jangan bagikan kode ini kepada siapapun. Tim Majelis Rental
                                            tidak pernah meminta kode OTP via telepon atau chat.
                                            Jika Anda tidak merasa mendaftar, abaikan email ini.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── Footer ── --}}
                    <tr>
                        <td style="background-color:#f4f4ef;padding:18px 36px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <p style="font-size:10px;font-weight:700;color:rgba(37,29,29,0.25);letter-spacing:0.12em;text-transform:uppercase;margin:0 0 3px;">
                                            MAJELIS RENTAL &copy; {{ date('Y') }}
                                        </p>
                                        <p style="font-size:10px;color:rgba(37,29,29,0.2);margin:0;line-height:1.5;">
                                            Email otomatis — harap tidak membalas pesan ini
                                        </p>
                                    </td>
                                    <td align="right" valign="middle">
                                        <p style="font-size:24px;font-weight:900;color:rgba(37,29,29,0.06);letter-spacing:0.08em;margin:0;">MR</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
