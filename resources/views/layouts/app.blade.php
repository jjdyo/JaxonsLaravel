<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - This is the title </title>

    <!-- Link to CSS files -->
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/content.css') }}">
</head>
<body>
<header>
    <h1>My Laravel Site</h1>
    <nav>
        <a href="{{ route('home') }}">Home</a>
        <a href="{{ route('about') }}">About</a>
        <a href="{{ route('contact') }}">Contact</a>
    </nav>
</header>

<main>
    <p>Pre-Content</p>
    @yield('content')
    <p>Post-content</p>
</main>

<footer>
    <p>This is the footer</p>
</footer>
</body>
</html>
