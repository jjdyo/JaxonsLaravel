<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - JaxonLaravel</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/content.css') }}">
    <link rel="stylesheet" href="{{ asset('css/starry-background.css') }}">
    @yield('styles') <!-- This will load CSS files from specific views -->
</head>

<body>
<div class="wrapper">

    <!-- Header -->
    <header class="starry-background">
        <div class="header-title content-shadow">
            <h1>Welcome to Jaxon Laravel</h1>
            <h2>Lorum ipsum or something</h2>
        </div>
        <nav class="nav-bar content-shadow">
            <div class="nav-links">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('contact') }}">Contact</a>
                <a href="{{ route('docs.index') }}">Docs</a>
            </div>
            <div class="nav-auth">
                @auth
                    <div class="dropdown">
                        <button class="dropbtn">{{ Auth::user()->email }} ▼</button>
                        <div class="dropdown-content">
                            <a href="{{ route('profile') }}">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="logout-btn">Logout</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}">Sign In</a>
                @endauth
            </div>
        </nav>

    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="starry-background">
        <p class="content-shadow">This is my footer</p>
    </footer>

</div>

<!-- Import JavaScript files -->
</body>

</html>
