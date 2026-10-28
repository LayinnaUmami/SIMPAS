<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kode Verifikasi</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.08);">

                {{-- Kepala --}}
                <tr>
                    <td style="background:#059669;padding:20px 28px;">
                        <p style="margin:0;color:#ffffff;font-size:18px;font-weight:800;letter-spacing:-.01em;">
                            {{ $namaAplikasi }}
                        </p>
                        <p style="margin:2px 0 0;color:#d1fae5;font-size:12px;">
                            Sistem Informasi Monitoring dan Penilaian Aktivitas Santri
                        </p>
                    </td>
                </tr>

                {{-- Isi --}}
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 6px;color:#0f172a;font-size:16px;font-weight:700;">
                            Halo, {{ $nama }}.
                        </p>
                        <p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.6;">
                           Berikut kode verifikasi untuk mengaktifkan akun Anda di
                            {{ $namaAplikasi }}.
                        </p>

                        {{-- Kotak kode --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center"
                                    style="background:#ecfdf5;border:2px dashed #34d399;border-radius:14px;padding:18px 12px;">
                                    <span style="display:block;color:#065f46;font-size:34px;font-weight:800;letter-spacing:.32em;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">
                                        {{ $kode }}
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:20px 0 0;color:#475569;font-size:14px;line-height:1.6;">
                            Kode berlaku <strong>{{ $menit }} menit</strong>. Kode hanya bisa dipakai
                            satu kali, dan permintaan baru akan membatalkan kode lama.
                        </p>

                        <p style="margin:16px 0 0;color:#64748b;font-size:13px;line-height:1.6;">
                            Mohon tidak meneruskan kode ini kepada siapa pun. Petugas
                            resmi tidak pernah meminta kode ini lewat telepon maupun WhatsApp.

                        </p>
                    </td>
                </tr>

                {{-- Kaki --}}
                <tr>
                    <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 28px;">
                        <p style="margin:0;color:#94a3b8;font-size:11px;line-height:1.6;">
                            Email ini dikirim otomatis. Jangan membalas pesan ini.
                            <br>
                            Komplek Roudlotul Qur'an Putri PP Al Ihya Ulumaddin.
                        </p>
                    </td>
                </tr>
            </table>

            <p style="margin:16px 0 0;color:#94a3b8;font-size:11px;text-align:center;">
                &copy; {{ now()->year }} {{ $namaAplikasi }}
            </p>
        </td>
    </tr>
</table>
</body>
</html>
