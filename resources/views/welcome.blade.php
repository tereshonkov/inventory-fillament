<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Облік активів') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f5f7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 50px -20px rgba(17, 24, 39, 0.25);
            padding: 48px 40px;
            max-width: 380px;
            width: 100%;
            text-align: center;
        }
        .logo-box {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: #1d2b45;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .06em;
            margin: 0 auto 20px;
        }
        h1 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        p.subtitle {
            font-size: 13px;
            color: #9aa2af;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px 0;
            background: #1d2b45;
            color: #fff;
            text-decoration: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            transition: background .15s;
        }
        .btn:hover { background: #26303e; }
        footer {
            margin-top: 24px;
            font-size: 11px;
            color: #c5c9d1;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-box">MVO</div>
        <h1>Облік активів</h1>
        <p class="subtitle">УІАП ГУНП в Харківській області</p>
        <a href="{{ url('/admin') }}" class="btn">Увійти в систему</a>
        <footer>&copy; {{ date('Y') }}</footer>
    </div>
</body>
</html>