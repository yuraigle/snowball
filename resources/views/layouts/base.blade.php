<!DOCTYPE html>
<html lang="ru" class="h-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Snowball @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>

<body>
<nav class="navbar navbar-expand-sm navbar-light bg-light bg-gradient shadow-sm">
    <div class="container">
        <a class="navbar-brand p-0" href="/">
            <img src="/logo.png" alt="Logo" width="48" height="48" class="d-inline-block"/>
        </a>

        <ul class="navbar-nav me-auto">
            <li class="nav-item ms-1">
                <a class="nav-link btn btn-sm btn-outline-light d-none d-md-block" href="/">
                    Портфель
                </a>
            </li>
            <li class="nav-item ms-1">
                <a class="nav-link btn btn-sm btn-outline-light" href="/advice">
                    Рекоммендации
                </a>
            </li>
        </ul>

        <div class="navbar-expand">
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item ms-1">
                        <a class="nav-link btn btn-sm btn-outline-light" href="#">
                            {{ auth()->user()['name'] }}
                        </a>
                    </li>
                    <li class="nav-item ms-1">
                        <a class="nav-link btn btn-sm btn-outline-light"
                           href="{{ route('logout') }}" title="Выход">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    </li>
                @endauth
                @guest
                    <li class="nav-item ms-1">
                        <a class="nav-link btn btn-sm btn-outline-light" href="{{ route('login') }}">
                            Войти
                        </a>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-3">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
@vite(['resources/js/app.js'])
<script src="https://kit.fontawesome.com/e0449c5598.js" crossorigin="anonymous"></script>
@yield('scripts')
</body>
</html>
