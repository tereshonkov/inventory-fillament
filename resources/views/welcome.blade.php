<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Облік активів') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0e1a;
            color: #fff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .glow {
            position: absolute;
            top: -20%;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 900px;
            background: radial-gradient(circle, rgba(29, 43, 69, 0.9) 0%, rgba(10, 14, 26, 0) 70%);
            pointer-events: none;
        }

        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 40%, transparent 100%);
            pointer-events: none;
        }

        nav {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 24px 0;
        }

        .logo-box {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: #fff;
            color: #1d2b45;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
        }

        nav span {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        main {
            position: relative;
            z-index: 2;
            max-width: 720px;
            margin: 0 auto;
            padding: 120px 24px 160px;
            text-align: center;
            max-height: 100vh;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .1);
            font-size: 12.5px;
            color: #9aa2af;
            margin-bottom: 28px;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ade80;
        }

        h1 {
            font-size: 44px;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin-bottom: 18px;
        }

        h1 span {
            background: linear-gradient(90deg, #fff, #9aa2af);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        p.subtitle {
            font-size: 16px;
            color: #9aa2af;
            line-height: 1.6;
            margin: 0 auto 40px;
            max-width: 480px;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 26px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14.5px;
            font-weight: 600;
            transition: all .15s;
        }

        .btn-primary {
            background: #fff;
            color: #0a0e1a;
        }

        .btn-primary:hover {
            background: #e5e7eb;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, .05);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .15);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, .1);
        }

        footer {
            position: relative;
            z-index: 2;
            text-align: center;
            padding-bottom: 40px;
            font-size: 12px;
            color: #5b6472;
        }
    </style>
</head>

<body>
    <div class="glow"></div>
    <div class="grid-overlay"></div>

    {{-- <nav>
        <div class="logo-box">MVO</div>
        <span>Облік активів</span>
    </nav> --}}

    <main>
        <div class="badge">
            <span class="badge-dot"></span>
            УІАП ГУНП в Харківській області
        </div>

        <h1>Система обліку<br><span>активів та майна</span></h1>

        <p class="subtitle">
            Внутрішня платформа для обліку, переміщення та списання
            техніки й майна підрозділу.
        </p>

        <div class="actions">
            <a href="{{ url('/admin') }}" class="btn btn-primary">Увійти в систему</a>
            <a href="mailto:tereshonkov.dima@gmail.com" class="btn btn-secondary">Запросити доступ</a>
        </div>
    </main>

    <footer>&copy; {{ date('Y') }} Облік активів</footer>
</body>

</html>
