<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Majelis Rental') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --clr-primary:     #655e44;
            --clr-primary-dk:  #4a4530;
            --clr-primary-lt:  #F2E8C6;
            --clr-chassis:     #251D1D;
            --clr-base:        #faf9f5;
            --clr-recessed:    #f4f4ef;
            --clr-raised:      #ffffff;
            --clr-utility:     #e7e9e2;
            --clr-text-main:   #251D1D;
            --clr-text-muted:  #655e44;
        }

        html, body { margin: 0; padding: 0; min-height: 100vh; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--clr-base);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ── Grid texture for left panel ── */
        .panel-texture {
            background-image:
                linear-gradient(rgba(242,232,198,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(242,232,198,0.045) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        /* ── Diagonal accent lines ── */
        .panel-accent-lines {
            background-image: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 24px,
                rgba(242,232,198,0.025) 24px,
                rgba(242,232,198,0.025) 25px
            );
        }

        /* ── Industrial input style ── */
        .inp {
            display: block;
            width: 100%;
            background: var(--clr-recessed);
            border: none;
            border-radius: 6px;
            padding: 11px 14px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--clr-text-main);
            outline: none;
            transition: background 0.18s ease, box-shadow 0.18s ease;
            box-shadow: none;
        }
        .inp::placeholder { color: rgba(37,29,29,0.28); }
        .inp:focus {
            background: var(--clr-raised);
            box-shadow:
                inset 0 -2px 0 0 var(--clr-primary),
                0 4px 20px rgba(101,94,68,0.09);
        }
        .inp:hover:not(:focus) { background: #edecea; }

        textarea.inp { resize: none; line-height: 1.5; }

        /* ── Primary button ── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 11px 24px;
            background: linear-gradient(150deg, #7a7255 0%, #655e44 50%, #4a4530 100%);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(101,94,68,0.32), inset 0 1px 0 rgba(255,255,255,0.12);
            text-decoration: none;
        }
        .btn-primary:hover {
            background: linear-gradient(150deg, #655e44 0%, #4a4530 50%, #37321f 100%);
            box-shadow: 0 6px 22px rgba(101,94,68,0.42), inset 0 1px 0 rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        .btn-primary:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(101,94,68,0.3); }

        /* ── Google button ── */
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 10px 20px;
            background: var(--clr-raised);
            border: none;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--clr-text-main);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.18s ease;
            box-shadow:
                0 1px 3px rgba(37,29,29,0.08),
                0 4px 12px rgba(37,29,29,0.06),
                inset 0 0 0 1px rgba(37,29,29,0.07);
        }
        .btn-google:hover {
            background: var(--clr-recessed);
            box-shadow:
                0 2px 6px rgba(37,29,29,0.1),
                0 8px 20px rgba(37,29,29,0.08),
                inset 0 0 0 1px rgba(37,29,29,0.1);
            transform: translateY(-1px);
        }
        .btn-google:active { transform: translateY(0); }

        /* ── Card ── */
        .auth-card {
            box-shadow:
                0 1px 2px rgba(37,29,29,0.04),
                0 4px 16px rgba(37,29,29,0.06),
                0 20px 60px rgba(37,29,29,0.1);
        }

        /* ── Tab switcher active ── */
        .tab-active {
            background: var(--clr-raised);
            color: var(--clr-text-main);
            box-shadow: 0 1px 4px rgba(37,29,29,0.12);
        }
        .tab-inactive {
            color: rgba(101,94,68,0.55);
        }
        .tab-inactive:hover { color: var(--clr-primary); }

        /* ── Form label ── */
        .lbl {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--clr-primary);
            margin-bottom: 6px;
        }

        /* ── Error text ── */
        .err-msg { font-size: 11px; color: #b91c1c; margin-top: 5px; display: block; }

        /* ── Separator ── */
        .sep-line { height: 1px; background: var(--clr-utility); flex: 1; }

        /* ── Status/success flash ── */
        .flash-success {
            background: var(--clr-primary-lt);
            color: var(--clr-primary-dk);
            font-size: 13px;
            font-weight: 500;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 14px;
        }

        /* ── Decorative dot cluster ── */
        .dot-grid {
            display: grid;
            grid-template-columns: repeat(6, 5px);
            gap: 5px;
        }
        .dot-grid span {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: rgba(242,232,198,0.18);
        }

        /* ── Checkbox ── */
        input[type="checkbox"].chk {
            appearance: none;
            -webkit-appearance: none;
            width: 16px; height: 16px;
            border-radius: 3px;
            background: var(--clr-recessed);
            border: none;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            transition: background 0.15s;
        }
        input[type="checkbox"].chk:checked {
            background: var(--clr-primary);
        }
        input[type="checkbox"].chk:checked::after {
            content: '';
            position: absolute;
            left: 4px; top: 2px;
            width: 5px; height: 8px;
            border: 2px solid #fff;
            border-top: none;
            border-left: none;
            transform: rotate(45deg);
        }
        input[type="checkbox"].chk:focus { outline: 2px solid rgba(101,94,68,0.3); outline-offset: 1px; }

        /* ── Smooth slide transitions ── */
        [x-cloak] { display: none !important; }

        /* ── Panel pulse ring (decorative) ── */
        @keyframes slowPulse {
            0%, 100% { opacity: 0.06; transform: scale(1); }
            50%       { opacity: 0.12; transform: scale(1.04); }
        }
        .pulse-ring {
            animation: slowPulse 6s ease-in-out infinite;
        }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
