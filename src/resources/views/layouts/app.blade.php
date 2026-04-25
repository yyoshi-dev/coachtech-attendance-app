<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap">

    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">

    {{-- Page CSS --}}
    @yield('css')
</head>

<body>
    <div class="app @yield('app-modifier')">
        <header class="header">
            <a href="@yield('header-logo-link')" class="header__logo">
                <img src="{{ asset('images/coachtech-header-logo.png') }}" alt="COACHTECH">
            </a>
            @yield('header-nav')
        </header>
        <main class="content">
            @yield('content')
        </main>
    </div>
</body>
</html>