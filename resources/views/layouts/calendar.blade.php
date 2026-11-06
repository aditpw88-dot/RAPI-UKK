<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .calendar-site-header {
                border-bottom: 3px solid #c96a1b;
                background: linear-gradient(110deg, #211c18, #3b2b1b);
                box-shadow: 0 2px 8px rgb(48 35 25 / 18%);
            }

            .calendar-site-header-inner {
                box-sizing: border-box;
                display: flex;
                width: 100%;
                max-width: 1240px;
                min-height: 76px;
                align-items: center;
                justify-content: space-between;
                margin: 0 auto;
                padding: 12px 24px;
            }

            .calendar-site-title {
                color: #fffaf0;
                font-weight: 700;
                text-decoration: none;
            }

            .calendar-login-button {
                display: inline-flex;
                min-width: 112px;
                min-height: 48px;
                box-sizing: border-box;
                align-items: center;
                justify-content: center;
                border: 2px solid #f5c27a;
                border-radius: 9px;
                padding: 10px 22px;
                background: #c96a1b;
                box-shadow: 0 3px 8px rgb(0 0 0 / 24%);
                color: #ffffff;
                font-size: 1.05rem;
                font-weight: 700;
                text-decoration: none;
                transition: background-color 150ms ease, transform 150ms ease;
            }

            .calendar-login-button:hover {
                transform: translateY(-1px);
                background: #a84e0b;
                color: #ffffff;
            }

            @media (max-width: 640px) {
                .calendar-site-header-inner {
                    padding-right: 16px;
                    padding-left: 16px;
                }
            }
        </style>
    </head>
    <body class="flex min-h-screen flex-col bg-white text-stone-900">
        <header class="calendar-site-header">
            <div class="calendar-site-header-inner">
                <a href="{{ route('calendar') }}" class="calendar-site-title">Kalender Sekolah</a>
                <a href="{{ route('login') }}" class="calendar-login-button">Login</a>
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>
        <x-site-footer />
    </body>
</html>