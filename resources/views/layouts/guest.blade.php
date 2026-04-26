<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Majelis Rental') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --clr-primary:     #655e44;
            --clr-primary-dk:  #4a4530;
            --clr-primary-lt:  #F2E8C6;
            --clr-chassis:     #1e1814;
            --clr-bg:          #EFE6CE;
            --clr-base:        #faf9f5;
            --clr-recessed:    #f0efea;
            --clr-raised:      #ffffff;
            --clr-utility:     #e2e0d8;
            --clr-text-main:   #251D1D;
            --clr-text-muted:  #655e44;
        }

        html, body {
            margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
        }

        /* ─── ROOT ─── */
        .auth-root {
            position: relative;
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
            background: var(--clr-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ─── BACKGROUND TEXTURE ─── */
        .bg-texture {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(101,94,68,0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(101,94,68,0.07) 1px, transparent 1px);
            background-size: 52px 52px;
            pointer-events: none;
        }

        /* ─── BACKGROUND GEO (static) ─── */
        .bg-geo {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0.25;
        }

        /* ─── MR WATERMARK ─── */
        .bg-watermark {
            position: absolute;
            font-size: 340px;
            font-weight: 900;
            color: rgba(101,94,68,0.06);
            line-height: 1;
            letter-spacing: -0.06em;
            right: -20px;
            bottom: -20px;
            font-family: 'Inter', sans-serif;
            pointer-events: none;
            user-select: none;
        }

        /* ─── TOP NAV ─── */
        .top-nav {
            position: absolute;
            top: 0; left: 0; right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 28px;
            z-index: 20;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: rgba(101,94,68,0.15);
            border: 1px solid rgba(101,94,68,0.25);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .brand-dots {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .brand-dot {
            width: 5px; height: 5px;
            border-radius: 50%;
        }

        /* ─── MAIN WRAPPER ─── */
        .auth-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 24px 16px;
        }

        /* ─── AUTH CARD ─── */
        .auth-card {
            background: rgba(255,255,255,0.98);
            border-radius: 18px;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(101,94,68,0.12),
                0 4px 20px rgba(101,94,68,0.12),
                0 40px 80px rgba(101,94,68,0.18);
        }

        /* ─── CARD HEADER ─── */
        .card-header {
            background: var(--clr-chassis);
            padding: 20px 22px 0 22px;
            position: relative;
            overflow: hidden;
        }
        .card-header-texture {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(242,232,198,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(242,232,198,0.03) 1px, transparent 1px);
            background-size: 36px 36px;
            pointer-events: none;
        }
        .card-header-accent {
            position: absolute;
            top: -40px; right: -40px;
            width: 140px; height: 140px;
            background: radial-gradient(circle, rgba(101,94,68,0.18) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ─── TAB SWITCHER ─── */
        .tab-track {
            position: relative;
            z-index: 2;
            display: flex;
            gap: 0;
            border-bottom: 1px solid rgba(242,232,198,0.08);
        }
        .tab-btn {
            flex: 1;
            padding: 13px 0 12px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.14em;
            border: none;
            background: transparent;
            color: rgba(242,232,198,0.28);
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
            cursor: pointer;
            transition: color 0.2s ease;
            position: relative;
        }
        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 20%; right: 20%;
            height: 2px;
            background: var(--clr-primary-lt);
            border-radius: 2px 2px 0 0;
            transform: scaleX(0);
            transition: transform 0.25s ease;
        }
        .tab-btn.active {
            color: var(--clr-primary-lt);
        }
        .tab-btn.active::after {
            transform: scaleX(1);
        }
        .tab-btn:not(.active):hover { color: rgba(242,232,198,0.55); }

        /* ─── FORMS WRAPPER ─── */
        .forms-grid {
            display: grid;
        }
        .forms-grid > div {
            grid-area: 1 / 1;
            min-width: 0;
        }

        /* ─── FORM ELEMENTS ─── */
        .lbl {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--clr-primary);
            margin-bottom: 6px;
        }

        .inp-wrap { position: relative; }
        .inp-wrap .inp { padding-right: 40px; }

        .inp {
            display: block;
            width: 100%;
            background: var(--clr-recessed);
            border: none;
            border-radius: 8px;
            padding: 11px 14px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--clr-text-main);
            outline: none;
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }
        .inp::placeholder { color: rgba(37,29,29,0.22); }
        .inp:focus {
            background: var(--clr-raised);
            box-shadow:
                inset 0 -2px 0 0 var(--clr-primary),
                0 4px 16px rgba(101,94,68,0.1);
        }
        .inp:hover:not(:focus) { background: #e9e7e1; }

        .eye-btn {
            position: absolute;
            right: 11px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer;
            color: rgba(37,29,29,0.28);
            display: flex; align-items: center; justify-content: center;
            padding: 4px; border-radius: 4px;
            transition: color 0.15s ease;
            line-height: 0;
        }
        .eye-btn:hover { color: var(--clr-primary); }

        .err-msg {
            display: block;
            font-size: 11px;
            color: #b91c1c;
            margin-top: 5px;
            font-weight: 500;
        }

        /* ─── BUTTONS ─── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 13px 24px;
            background: linear-gradient(145deg, #7a7255 0%, #655e44 55%, #4a4530 100%);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.22s ease;
            box-shadow: 0 4px 16px rgba(101,94,68,0.35), inset 0 1px 0 rgba(255,255,255,0.14);
            text-decoration: none;
        }
        .btn-primary:hover {
            background: linear-gradient(145deg, #655e44 0%, #4a4530 55%, #37321f 100%);
            box-shadow: 0 8px 26px rgba(101,94,68,0.45), inset 0 1px 0 rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        .btn-primary:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(101,94,68,0.3); }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 11px 20px;
            background: var(--clr-raised);
            border: none;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--clr-text-main);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(37,29,29,0.06), 0 4px 14px rgba(37,29,29,0.06), inset 0 0 0 1px rgba(37,29,29,0.08);
        }
        .btn-google:hover {
            background: var(--clr-recessed);
            box-shadow: 0 2px 8px rgba(37,29,29,0.09), 0 10px 24px rgba(37,29,29,0.08), inset 0 0 0 1px rgba(37,29,29,0.12);
            transform: translateY(-1px);
        }
        .btn-google:active { transform: translateY(0); }

        /* ─── SEPARATOR ─── */
        .sep-line { height: 1px; background: var(--clr-utility); flex: 1; }

        /* ─── FLASH ─── */
        .flash-success {
            background: var(--clr-primary-lt);
            color: var(--clr-primary-dk);
            font-size: 13px;
            font-weight: 500;
            padding: 10px 14px;
            border-radius: 7px;
            margin-bottom: 14px;
        }
        .flash-error {
            background: #fff1f1;
            border-left: 3px solid #b91c1c;
            border-radius: 7px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        /* ─── CHECKBOX ─── */
        input[type="checkbox"].chk {
            appearance: none; -webkit-appearance: none;
            width: 16px; height: 16px;
            border-radius: 4px;
            background: var(--clr-recessed);
            border: none; cursor: pointer;
            position: relative; flex-shrink: 0;
            transition: background 0.15s;
        }
        input[type="checkbox"].chk:checked { background: var(--clr-primary); }
        input[type="checkbox"].chk:checked::after {
            content: '';
            position: absolute;
            left: 4px; top: 2px;
            width: 5px; height: 8px;
            border: 2px solid #fff;
            border-top: none; border-left: none;
            transform: rotate(45deg);
        }
        input[type="checkbox"].chk:focus { outline: 2px solid rgba(101,94,68,0.3); outline-offset: 1px; }

        /* ─── BOTTOM FOOTER ─── */
        .bottom-footer {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 5;
        }
        .footer-line {
            height: 1px; flex: 1;
            background: rgba(101,94,68,0.15);
        }
        .footer-text {
            color: rgba(101,94,68,0.4);
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.22em;
            margin: 0 14px;
            white-space: nowrap;
        }

        /* ─── ALPINE CLOAK ─── */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
