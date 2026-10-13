# Event Attendance Universitas Bakrie

Aplikasi web pengelolaan acara & presensi QR untuk Ormawa Universitas Bakrie.
Capstone Kelompok 7.

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/nrzraf/presensi-ormawa)

## Fitur

- Login & peran (anggota, panitia, pembina, admin), rate-limit 5x/10 menit.
- Kelola acara: draft, publikasi, berlangsung, ditutup, dibatalkan.
- Pendaftaran peserta + QR token unik per peserta.
- Presensi scan QR (kamera) dan input manual (cadangan).
- Proposal kegiatan: ajukan, tinjau, setujui/tolak/revisi.
- Notifikasi: email acara baru, perubahan jadwal, pengingat H-1 (in-app + email).
- Laporan & ekspor Excel, log audit, manajemen anggota.

## Stack

PHP 8.5 (runtime `vercel-php`) + PostgreSQL (Supabase). Tanpa Composer, tanpa dependency eksternal.

## Deploy ke Vercel

1. Import repo ini di Vercel.
2. Siapkan database PostgreSQL di [Supabase](https://supabase.com) (Project Settings > Database >
   Connection string > **Session pooler** / port `5432`). Ganti `[YOUR-PASSWORD]` dengan kata sandi
   database. Port `5432` (session) dipakai agar prepared statement PDO berjalan normal.
3. Tambahkan Environment Variable di Vercel:

   | Variable | Nilai |
   |---|---|
   | `DATABASE_URL` | `postgresql://postgres:<password>@<host>:5432/postgres` |
   | `APP_BASE_URL` | `https://<nama-project>.vercel.app` |

4. Deploy, lalu buka `https://<nama-project>.vercel.app/install.php` sekali untuk membuat tabel & data contoh.
5. Buka aplikasi: `https://<nama-project>.vercel.app/`.

Opsi email (FR-10) - kosongkan bila tidak perlu, email otomatis dicatat ke tabel `mail_log`:

| Variable | Contoh |
|---|---|
| `SMTP_HOST` | `smtp.gmail.com` |
| `SMTP_PORT` | `587` |
| `SMTP_SECURE` | `tls` |
| `SMTP_USER` | `alamat@gmail.com` |
| `SMTP_PASS` | app password |

## Jalankan lokal

```sh
php -S localhost:8000 api/index.php
```

Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` (atau `DATABASE_URL`) pada environment.

## Akun Demo

Semua password: `password123`

| Peran | Email |
|---|---|
| Admin | `admin@ormawa.bakrie.ac.id` |
| Panitia | `panitia@student.bakrie.ac.id` |
| Pembina | `pembina@ormawa.bakrie.ac.id` |
| Anggota | `anggota@student.bakrie.ac.id` |

## Struktur

```
api/index.php      front controller (Vercel)
index.php          router aplikasi (?r=<route>)
config.php         konfigurasi (baca environment)
db.php             koneksi PDO + helper query
session.php        sesi berbasis database
helpers.php        auth, CSRF, audit, format
layout.php         kerangka UI
mailer.php         email + notifikasi (FR-10)
install.php        pembuat tabel + data contoh
cron_reminder.php  pengingat H-1
pages/             halaman aplikasi
```

## Tabel

`role` · `member` · `event` · `registration` · `attendance` · `proposal` ·
`proposal_file` · `notification` · `audit_log` · `login_attempt` · `mail_log` · `app_session`
