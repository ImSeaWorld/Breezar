<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="/favicon.svg">
    <link rel="icon" type="image/png" href="/favicon.svg">

    <title>{{ config('app.name', 'Breezar') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    @routes

    @vite(['resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
