<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoachTech 勤怠管理アプリ</title>
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <div class="header__logo">
                <a href="{{ route('attendance.action') }}">
                    <img src="{{ asset('img/logo.svg') }}" alt="ロゴ" class="header__logo-image">
                </a>
            </div>
            <nav class="header__nav">
                @yield('links')
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="main__container">
            @yield('main')
        </div>
    </main>
    @yield('script')
</body>
</html>
