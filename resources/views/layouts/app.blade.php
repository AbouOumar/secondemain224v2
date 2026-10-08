<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', config('app.name', 'Seconde Main 224'))</title>
    @php
        // Le contenu d'une @section('…', $valeur) est déjà échappé par Blade :
        // on l'affiche tel quel pour éviter un double échappement (&amp;#039;).
        $metaDescription = trim($__env->yieldContent('meta_description'))
            ?: e("Achetez et vendez des articles d'occasion en Guinée : paiement sécurisé par Orange Money et MTN Mobile Money, livraison par motards partenaires.");
    @endphp
    <meta name="description" content="{!! $metaDescription !!}">
    {{-- Aperçu des liens partagés (WhatsApp, Facebook, X) --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="{!! $metaDescription !!}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('assets/img/hero-bg.jpg'))">
    <meta name="twitter:card" content="summary_large_image">
    @stack('meta')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    @stack('styles')
    <style>
        :root {
            --primary: #e66a00;
            --primary-dark: #cc5500;
            --primary-light: #ff8533;
            --secondary: #f8f9fa;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --bg: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }
        
        body {
            font-family: "Open Sans", sans-serif;
            color: #444444;
            background-color: #fff;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: "Raleway", sans-serif;
            font-weight: 700;
            color: #222222;
        }
        
        a {
            color: #e66a00;
            text-decoration: none;
        }
        
        a:hover {
            color: #cc5500;
            text-decoration: none;
        }
        
        .navbar {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: all 0.5s;
        }
        
        .navbar.scrolled {
            background: #e66a00;
        }
        
        .navbar-brand {
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .navbar-brand h1 {
            color: #fff;
            margin: 0;
        }
        
        .navbar-brand h1 a,
        .navbar-brand h1 a:hover {
            color: #fff;
            text-decoration: none;
        }
        
        .nav-link {
            color: #fff !important;
            font-weight: 500;
            padding: 8px 15px !important;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: #fff !important;
        }
        
        .btn-primary {
            background-color: #e66a00;
            border-color: #e66a00;
            color: #fff;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #cc5500;
            border-color: #cc5500;
            color: #fff;
            transform: translateY(-2px);
        }
        
        .btn-outline-primary {
            border-color: #e66a00;
            color: #e66a00;
        }
        
        .btn-outline-primary:hover {
            background-color: #e66a00;
            border-color: #e66a00;
            color: #fff;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }
        
        .card-img-top {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .form-control {
            border-radius: 50px;
            border: 1px solid #ced4da;
            padding: 12px 20px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #e66a00;
            box-shadow: 0 0 0 0.2rem rgba(230,106,0,0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .btn {
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-lg {
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        
        .section-title {
            text-align: center;
            padding-bottom: 40px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            position: relative;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            display: block;
            width: 60px;
            height: 3px;
            background: #e66a00;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .section-title p {
            color: #777777;
            max-width: 500px;
            margin: 0 auto 20px auto;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: #6c757d;
            content: "/ ";
        }
        
        .breadcrumb-item.active {
            color: #6c757d;
        }
        
        .breadcrumb-item a {
            color: #e66a00;
        }
        
        .breadcrumb-item a:hover {
            color: #cc5500;
        }
        
        .pagination .page-link {
            color: #e66a00;
            border: #e66a00;
            border-radius: 50%;
            margin: 0 5px;
        }
        
        .pagination .page-link:hover {
            background-color: #e66a00;
            color: #fff;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #e66a00;
            border-color: #e66a00;
            color: #fff;
        }
        
        .badge {
            font-weight: 600;
            padding: 0.5em 0.9em;
            border-radius: 0.5rem;
        }
        
        .badge-primary {
            background-color: #e66a00;
            color: #fff;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: #fff;
        }
        
        .badge-success {
            background-color: #28a745;
            color: #fff;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: #fff;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: #fff;
        }
        
        .list-group-item {
            border: none;
            border-radius: 0;
            padding: 1rem 1.25rem;
        }
        
        .list-group-item-action {
            width: 100%;
            color: #495057;
            text-align: inherit;
        }
        
        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }
        
        .list-group-item-action:active {
            color: #212529;
            background-color: #e9ecef;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 60px 0 20px 0;
        }
        
        footer h5 {
            font-family: "Raleway", sans-serif;
            font-weight: 700;
            margin-bottom: 20px;
            color: #fff;
        }
        
        .footer p {
            color: #777777;
        }
        
        .footer a {
            color: #e66a00;
        }
        
        .footer a:hover {
            color: #cc5500;
        }
        
        .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(230,106,0,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background-color: #e66a00;
            color: #fff;
        }
        
        .btn-back-to-top {
            position: fixed;
            display: none;
            background: #e66a00;
            color: #fff;
            width: 40px;
            height: 40px;
            text-align: center;
            border-radius: 50px;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            font-size: 20px;
            border: none;
            outline: none;
            cursor: pointer;
        }
        
        .btn-back-to-top:hover {
            background: #cc5500;
            color: #fff;
        }
        
        /* Menu déplié dès 768px (ex. mode « version ordinateur » sur téléphone, ~980px) :
           on resserre les liens pour que tout tienne sur une ligne. */
        @media (min-width: 768px) and (max-width: 1199px) {
            .navbar-expand-md .navbar-nav .nav-link {
                padding-left: 0.4rem !important;
                padding-right: 0.4rem !important;
                font-size: 0.85rem;
                white-space: nowrap;
            }
            .navbar-expand-md .navbar-nav {
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            .navbar-expand-md .navbar-brand .brand {
                font-size: 1.05rem;
            }
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .nav-link {
                padding: 8px 10px !important;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .btn-primary {
                padding: 10px 25px;
                font-size: 0.9rem;
            }
        }
    </style>
    <style>
        .article-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        .article-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.15) !important;
        }
        .article-card .card-img-top {
            transition: transform 0.4s ease;
        }
        .article-card:hover .card-img-top {
            transform: scale(1.08);
        }
        .article-card:hover .title {
            color: var(--primary);
        }
        @media (prefers-reduced-motion: reduce) {
            .article-card,
            .article-card .card-img-top {
                transition: none;
            }
            .article-card:hover {
                transform: none;
            }
            .article-card:hover .card-img-top {
                transform: none;
            }
        }
        /* Transition animée entre les pages (ex: liste -> détail d'une
           annonce au clic sur "Voir") via la View Transitions API native du
           navigateur. Amélioration progressive : sans effet sur les
           navigateurs qui ne la supportent pas encore, aucun JS requis. */
        @media (prefers-reduced-motion: no-preference) {
            @view-transition {
                navigation: auto;
            }
            ::view-transition-old(root) {
                animation: 220ms ease-in both sm224-page-out;
            }
            ::view-transition-new(root) {
                animation: 420ms cubic-bezier(.22,1,.36,1) both sm224-page-in;
            }
            @keyframes sm224-page-out {
                to { opacity: 0; transform: scale(0.97); }
            }
            @keyframes sm224-page-in {
                from { opacity: 0; transform: scale(1.03); }
            }
        }
        .price-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(230,106,0,0.9);
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 6px;
            z-index: 2;
        }
        .favorite-btn {
            position: absolute;
            top: 8px;
            left: 8px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.9);
            color: var(--gray-600, #6c757d);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            z-index: 3;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
            transition: transform 0.15s ease, color 0.15s ease;
        }
        .favorite-btn:hover {
            transform: scale(1.12);
        }
        .favorite-btn.is-saved,
        .favorite-btn[data-saved="1"] {
            color: #e63757;
        }
        .share-popup {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            min-width: 180px;
            z-index: 10;
            padding: 4px;
        }
        .share-popup .btn {
            border-radius: 6px;
            font-size: 0.8rem;
        }
        .share-popup .btn:hover {
            background: var(--gray-100);
        }
        .cat-item {
            cursor: pointer;
            transition: transform 0.2s;
            padding: 8px 12px;
            border-radius: 10px;
            background: var(--gray-100);
            min-width: 80px;
        }
        .cat-item:hover {
            transform: scale(1.05);
            background: var(--primary);
            color: #fff;
        }
        .cat-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 4px;
            font-size: 1.2rem;
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .cat-item:hover .cat-circle {
            background: var(--primary-light);
            color: #fff;
        }
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .bx-search {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1.2rem;
            z-index: 5;
        }
        .search-wrapper input {
            padding-left: 40px;
            border-radius: 50px !important;
            border: 2px solid var(--gray-200);
        }
        .search-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(230,106,0,0.15);
        }
        .article-card .card-img-top {
            height: 180px;
            object-fit: cover;
        }

        /* Transition douce entre les pages : la page qui s'affiche apparaît
           en fondu, celle qu'on quitte s'efface avant la navigation (voir
           le script de fin de page). */
        body {
            opacity: 0;
            animation: pageFadeIn 0.45s ease forwards;
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            /* transform: none (et non translateY(0)) : une transformation, même
               neutre, conservée par "forwards" fait de <body> le repère des
               éléments position:fixed et décale les fenêtres modales. */
            to { opacity: 1; transform: none; }
        }
        body.page-transitioning {
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.22s ease, transform 0.22s ease;
            pointer-events: none;
        }
        @media (prefers-reduced-motion: reduce) {
            body {
                animation: none;
                opacity: 1;
            }
            body.page-transitioning {
                transition: none;
                opacity: 1;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark" style="padding: 0;">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                    <img src="{{ asset('assets/img/icon.png') }}" width="44" height="44" style="border-radius: 50px; object-fit: cover;">
                    <span class="brand">Seconde Main 224</span>
                </a>
                
                
                <!-- Burger -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <i class='bx bx-menu' style="font-size: 32px; color: white;"></i>
                </button>
                
                <!-- Menu -->
                <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                    <ul class="navbar-nav align-items-md-center text-center">
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/') }}">Accueil</a>
                        </li>
                        @auth
                        @php $role = auth()->user()->role?->value; @endphp
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/articles/create') }}">Publier</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/profile/listings') }}">Mes annonces</a>
                        </li>
                        @if($role === 'revendeur_pro')
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/seller/pro/magasin') }}">Mon Magasin</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/seller/pro/tableau-de-bord') }}">Tableau de bord</a>
                        </li>
                        @endif
                        @if($role === 'motard')
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/motard/tableau-de-bord') }}">Livraisons</a>
                        </li>
                        @endif
                        @if($role === 'admin')
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ route('admin.dashboard') }}">Admin</a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/notifications') }}">Notifications
                                @auth
                                @php
                                    $unreadNotif = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count();
                                @endphp
                                @if($unreadNotif > 0)
                                    <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.65rem;" id="notif-badge">{{ $unreadNotif }}</span>
                                @endif
                                @endauth
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/messages') }}">Messages
                                @auth
                                @php
                                    $unreadMsg = \App\Models\Message::where('receiver_id', Auth::id())->where('is_read', false)->count();
                                @endphp
                                @if($unreadMsg > 0)
                                    <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.65rem;">{{ $unreadMsg }}</span>
                                @endif
                                @endauth
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/profile') }}">Profil</a>
                        </li>
                        <li class="nav-item mt-2 mt-md-0">
                            <a class="btn btn-outline-light px-3" href="{{ url('/logout') }}">
                                <i class='bx bx-log-out' style="font-size: 1.2rem;"></i>
                            </a>
                        </li>
                        @else
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/login') }}">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="{{ url('/register') }}">S'inscrire</a>
                        </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>
        
        <main>
            @if(session('success'))
                <div class="container mt-3">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class='bx bx-check-circle me-1'></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="container mt-3">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class='bx bx-error-circle me-1'></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif
            @auth
                @if(! auth()->user()->hasRealPhone() && ! request()->routeIs('profile.edit'))
                    <div class="container mt-3">
                        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0" role="alert">
                            <span><i class='bx bx-phone me-1'></i> Ajoutez votre numéro de téléphone pour pouvoir acheter, vendre et être contacté après une commande.</span>
                            <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-dark">Compléter mon profil</a>
                        </div>
                    </div>
                @endif
            @endauth
            @auth
                @if(auth()->user()->email && ! auth()->user()->hasVerifiedEmail())
                    <div class="container mt-3">
                        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0" role="alert">
                            <span><i class='bx bx-envelope me-1'></i> Confirmez votre adresse e-mail <strong>{{ auth()->user()->email }}</strong> grâce au lien que nous vous avons envoyé.</span>
                            <form method="POST" action="{{ route('verification.send') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-dark">Renvoyer le lien</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endauth
            @yield('content')
        </main>
        
        <!-- Footer -->
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container">
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <h5>À propos</h5>
                        <p>Seconde Main 224 - Marketplace Guinée</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h5>Liens rapides</h5>
                        <ul class="list-unstyled">
                            <li><a href="{{ url('/nous') }}" class="text-white text-decoration-none">Qui sommes-nous ?</a></li>
                            <li><a href="{{ url('/contact') }}" class="text-white text-decoration-none">Contact</a></li>
                            <li><a href="{{ route('legal.terms') }}" class="text-white text-decoration-none">Conditions d'utilisation</a></li>
                            <li><a href="{{ route('legal.privacy') }}" class="text-white text-decoration-none">Confidentialité</a></li>
                        </ul>
                    </div>
                    @if(array_filter(config('legal.social')))
                    <div class="col-md-4 mb-3">
                        <h5>Suivez-nous</h5>
                        <div class="d-flex gap-3 justify-content-center">
                            @if(config('legal.social.facebook'))
                                <a href="{{ config('legal.social.facebook') }}" class="text-white" target="_blank" rel="noopener" aria-label="Facebook"><i class='bx bxl-facebook-circle'></i></a>
                            @endif
                            @if(config('legal.social.whatsapp'))
                                <a href="https://wa.me/{{ preg_replace('/\D+/', '', config('legal.social.whatsapp')) }}" class="text-white" target="_blank" rel="noopener" aria-label="WhatsApp"><i class='bx bxl-whatsapp'></i></a>
                            @endif
                            @if(config('legal.social.twitter'))
                                <a href="{{ config('legal.social.twitter') }}" class="text-white" target="_blank" rel="noopener" aria-label="X (Twitter)"><i class='bx bxl-twitter'></i></a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                <div class="text-center mt-3 border-top border-secondary-subtle pt-3">
                    <p class="mb-0">&copy; {{ now()->year }} Seconde Main 224. Tous droits réservés.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/api.js"></script>
    
    @stack('scripts')
    
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration.scope);
                    })
                    .catch(error => {
                        console.log('SW registration failed: ', error);
                    });
            });
        }
    </script>
    <script>
        function copyLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Lien copié !');
            });
        }
    </script>
    <script>
        // Bouton cœur (favoris) réutilisé sur toutes les grilles d'annonces.
        // btnEl porte data-saved="1"/"0" (précalculé côté serveur via le
        // View Composer $savedIds) pour un état correct au premier rendu.
        function toggleFavorite(articleId, btnEl) {
            @guest
                window.location.href = '{{ route('login') }}';
                return;
            @endguest
            if (btnEl.dataset.loading === '1') return;
            btnEl.dataset.loading = '1';

            fetch('{{ url('/saved') }}/' + articleId, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .then(res => res.json())
            .then(data => {
                btnEl.dataset.saved = data.saved ? '1' : '0';
                const icon = btnEl.querySelector('i');
                if (icon) icon.className = data.saved ? 'bx bxs-heart' : 'bx bx-heart';
                btnEl.classList.toggle('is-saved', data.saved);
                const label = btnEl.querySelector('span');
                if (label) label.textContent = data.saved ? 'Enregistré' : 'Enregistrer';
            })
            .catch(() => {})
            .finally(() => { btnEl.dataset.loading = '0'; });
        }
    </script>
    <script>
        // Transition de page : au clic sur un lien interne (ex: "Voir" une
        // annonce), on fait un fondu de sortie avant de naviguer, pour un
        // rendu plus fluide qu'un rechargement brut. La page d'arrivée
        // apparaît elle-même en fondu via l'animation pageFadeIn ci-dessus.
        (function () {
            var TRANSITION_MS = 220;

            document.addEventListener('click', function (e) {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                var link = e.target.closest('a[href]');
                if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

                var href = link.getAttribute('href');
                if (!href || href.startsWith('#') || /^(mailto|tel|javascript):/i.test(href)) return;

                var url;
                try { url = new URL(href, window.location.href); } catch (err) { return; }
                if (url.origin !== window.location.origin) return;
                if (url.href === window.location.href) return;

                e.preventDefault();
                document.body.classList.add('page-transitioning');
                setTimeout(function () { window.location.href = url.href; }, TRANSITION_MS);
            });

            // Si l'utilisateur revient en arrière (page restaurée depuis le
            // cache du navigateur), on s'assure que le fondu de sortie ne
            // reste pas figé et masque la page.
            window.addEventListener('pageshow', function () {
                document.body.classList.remove('page-transitioning');
            });
        })();
    </script>
</body>
</html>