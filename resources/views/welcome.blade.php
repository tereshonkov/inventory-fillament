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

        main {
            background-image: url('{{ asset('background.png') }}');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .text {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 600px;
        }

        .text h1 {
            font-size: 3rem;
            color: #fff;
        }

        .subtitle {
            font-size: 1.5rem;
            color: #fff;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <main class="content">

        <div class="text">
            <h1>Система обліку<br><span>активів та майна</span></h1>

            <p class="subtitle">
                Внутрішня платформа для обліку, переміщення та списання
                техніки й майна підрозділу.
            </p>
        </div>

        <div class="actions">
            <a href="{{ url('/admin') }}" class="btn btn-primary">Увійти в систему</a>
            <a href="mailto:tereshonkov.dima@gmail.com" class="btn btn-secondary">Запросити доступ</a>
        </div>
    </main>
</body>

</html>