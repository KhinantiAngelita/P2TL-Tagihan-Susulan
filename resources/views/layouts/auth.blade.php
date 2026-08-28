<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PLN Persero')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy-950: #071233;
            --navy-900: #0a1a52;
            --navy-800: #0e2170;
            --yellow: #ffc905;
            --yellow-dark: #f2b900;
            --gray-500: #6b7280;
            --gray-400: #9aa3b2;
            --border: #e5e7eb;
            --field-bg: #f2f3f5;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; min-height: 100%;
            font-family: 'Inter', Arial, sans-serif;
            color: #1a1f36;
        }
        .auth-wrap {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ---------- LEFT PANEL ---------- */
        .auth-side {
            flex: 0 0 59%;
            position: relative;
            background:
                linear-gradient(160deg, var(--navy-900) 0%, var(--navy-950) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            overflow: hidden;
        }
        .auth-side::before {
            content: "";
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 64px 64px;
            -webkit-mask-image: radial-gradient(circle at 50% 40%, #000 0%, transparent 75%);
                    mask-image: radial-gradient(circle at 50% 40%, #000 0%, transparent 75%);
            pointer-events: none;
        }

        .auth-brand {
            position: absolute;
            top: 48px; left: 48px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 2;
        }
        .auth-brand .logo-box {
            width: 110px; height: 110px;
            background: transparent;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .auth-brand .logo-box img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .auth-brand .logo-text { line-height: 1.1; }
        .auth-brand .logo-text .pln { font-size: 22px; font-weight: 800; letter-spacing: .5px; }
        .auth-brand .logo-text .persero { font-size: 11px; font-weight: 600; letter-spacing: 3px; color: #b9c2e0; }

        .auth-illustration { z-index: 2; margin: 96px 0 28px; }

        .auth-copy { z-index: 2; text-align: center; max-width: 460px; }
        .auth-copy h1 {
            font-size: 30px; font-weight: 800; line-height: 1.25; margin: 0 0 14px;
        }
        .auth-copy p {
            font-size: 15px; line-height: 1.6; color: #aab2cc; margin: 0;
        }

        .auth-stats {
            z-index: 2;
            display: flex;
            gap: 48px;
            margin-top: 40px;
        }
        .auth-stats div { text-align: center; }
        .auth-stats .num { font-size: 22px; font-weight: 800; color: var(--yellow); }
        .auth-stats .label { font-size: 12px; color: #9aa3c2; margin-top: 2px; }

        /* ---------- RIGHT PANEL ---------- */
        .auth-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 40px 32px;
        }
        .auth-form-box { width: 100%; max-width: 420px; }
        .auth-form-box h2 { font-size: 26px; font-weight: 800; color: var(--navy-950); margin: 0 0 8px; }
        .auth-form-box .subtitle { font-size: 14px; color: var(--gray-500); margin: 0 0 32px; }

        .field { margin-bottom: 20px; }
        .field label {
            display: block; font-size: 13px; font-weight: 700;
            color: #1a1f36; margin-bottom: 8px;
        }
        .field .input-wrap { position: relative; }
        .field .input-wrap svg.leading-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: var(--gray-400);
        }
        .field input {
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            padding: 13px 14px 13px 42px;
            background: var(--field-bg);
            border: 1px solid transparent;
            border-radius: 10px;
            color: #1a1f36;
            outline: none;
        }
        .field input::placeholder { color: var(--gray-400); }
        .field input:focus { border-color: var(--navy-800); background: #fff; }
        .field .toggle-eye {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: var(--gray-400); cursor: pointer; background: none; border: none; padding: 0;
        }

        .row-between {
            display: flex; align-items: center; justify-content: space-between;
            margin: -6px 0 24px; font-size: 13px;
        }
        .remember { display: flex; align-items: center; gap: 8px; color: #374151; }
        .remember input { width: 16px; height: 16px; accent-color: var(--navy-800); }
        .link { color: var(--navy-800); font-weight: 700; text-decoration: none; }
        .link:hover { text-decoration: underline; }

        .btn-primary {
            width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--yellow);
            color: var(--navy-950);
            font-family: inherit;
            font-size: 15px; font-weight: 800;
            padding: 14px 20px;
            border: none; border-radius: 10px;
            cursor: pointer;
        }
        .btn-primary:hover { background: var(--yellow-dark); }
        .btn-primary svg { width: 18px; height: 18px; }

        .auth-footer-text {
            text-align: center; font-size: 14px; color: var(--gray-500); margin-top: 28px;
        }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 14px; font-weight: 700; color: var(--navy-800); text-decoration: none; margin-top: 28px;
        }
        .back-link svg { width: 16px; height: 16px; }

        .help-fab {
            position: fixed; right: 28px; bottom: 28px;
            width: 44px; height: 44px; border-radius: 50%;
            background: var(--navy-950); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 16px;
            box-shadow: 0 6px 16px rgba(0,0,0,.25);
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .auth-side { display: none; }
            .auth-main { flex: 1 1 100%; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="auth-wrap">
    <div class="auth-side">
        <div class="auth-brand">
            <div class="logo-box">
                <img src="{{ asset('logo/Logo 2.png') }}" alt="Logo PLN">
            </div>
        </div>

        <div class="auth-illustration">
            <svg width="230" height="180" viewBox="0 0 230 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="115" cy="38" r="26" stroke="#4a5aa8" stroke-width="1.5"/>
                <path d="M119 24 108 42h9l-2 14 13-18h-9l2-14Z" fill="#ffc905"/>
                <path d="M20 92 L210 92" stroke="#4a5aa8" stroke-width="1.5"/>
                <path d="M20 100 L210 100" stroke="#4a5aa8" stroke-width="1.5"/>
                <circle cx="75" cy="92" r="2.5" fill="#ffc905"/>
                <circle cx="115" cy="88" r="2.5" fill="#ffc905"/>
                <circle cx="155" cy="92" r="2.5" fill="#ffc905"/>
                <path d="M30 92 20 150 M30 92 40 150" stroke="#4a5aa8" stroke-width="1.5"/>
                <path d="M200 92 190 150 M200 92 210 150" stroke="#4a5aa8" stroke-width="1.5"/>
                <path d="M10 90 30 78 50 90" stroke="#4a5aa8" stroke-width="1.5" fill="none"/>
                <path d="M180 90 200 78 220 90" stroke="#4a5aa8" stroke-width="1.5" fill="none"/>
                <rect x="94" y="98" width="42" height="34" rx="6" stroke="#4a5aa8" stroke-width="1.5"/>
                <rect x="102" y="108" width="10" height="14" rx="2" fill="#4a5aa8"/>
                <rect x="118" y="108" width="10" height="14" rx="2" fill="#4a5aa8"/>
                <path d="M0 158 Q 28 142 57 158 T 115 158 T 173 158 T 230 158" stroke="#4a5aa8" stroke-width="1.5" fill="none"/>
            </svg>
        </div>

        <div class="auth-copy">
            <h1>Sistem Manajemen<br>Energi Terpadu</h1>
            <p>Pantau, kelola, dan optimalkan distribusi listrik nasional dalam satu platform terintegrasi.</p>
        </div>

        <div class="auth-stats">
            <div><div class="num">82,4 Jt</div><div class="label">Pelanggan</div></div>
            <div><div class="num">71 GW</div><div class="label">Kapasitas</div></div>
            <div><div class="num">99,7%</div><div class="label">Uptime</div></div>
        </div>
    </div>

    <div class="auth-main">
        <div class="auth-form-box">
            @yield('form')
        </div>
    </div>
</div>

<a href="#" class="help-fab" title="Bantuan">?</a>
@stack('scripts')
</body>
</html>