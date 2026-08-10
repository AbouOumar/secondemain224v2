<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · Seconde Main 224</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #e66a00;
            --primary-dark: #cc5500;
            --sidebar-bg: #1f2430;
            --sidebar-hover: #2a3040;
        }
        body { background: #f4f5f7; font-family: "Segoe UI", Arial, sans-serif; }
        .admin-sidebar {
            width: 240px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }
        .admin-sidebar .brand {
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 20px;
            display: block;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 12px 20px;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 0;
        }
        .admin-sidebar .nav-link i { font-size: 1.15rem; }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: var(--sidebar-hover);
            color: #fff;
            border-left: 3px solid var(--primary);
        }
        .admin-content { margin-left: 240px; padding: 24px 28px; }
        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            height: 100%;
        }
        .stat-card .value { font-size: 1.6rem; font-weight: 700; color: #212529; }
        .stat-card .label { color: #6c757d; font-size: 0.85rem; }
        .card { border: none; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .table thead th { font-size: 0.78rem; text-transform: uppercase; color: #6c757d; border-top: none; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .badge-soft-success { background: #d4edda; color: #155724; }
        .badge-soft-warning { background: #fff3cd; color: #856404; }
        .badge-soft-danger { background: #f8d7da; color: #721c24; }
        .badge-soft-secondary { background: #e2e3e5; color: #383d41; }
        .badge-soft-info { background: #d1ecf1; color: #0c5460; }
        @media (max-width: 900px) {
            .admin-sidebar { width: 70px; }
            .admin-sidebar .brand span, .admin-sidebar .nav-link span { display: none; }
            .admin-content { margin-left: 70px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="admin-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <i class='bx bxs-dashboard'></i> <span>Admin · SM224</span>
        </a>
        <div class="nav flex-column pt-2">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class='bx bx-home'></i><span>Tableau de bord</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class='bx bx-user'></i><span>Utilisateurs</span>
            </a>
            <a href="{{ route('admin.articles.index') }}" class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
                <i class='bx bx-check-shield'></i><span>Modération annonces</span>
            </a>
            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class='bx bx-category'></i><span>Catégories</span>
            </a>
            <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                <i class='bx bx-credit-card'></i><span>Paiements</span>
            </a>
            <a href="{{ route('admin.partners.index') }}" class="nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}">
                <i class='bx bx-store'></i><span>Magasins pro</span>
            </a>
            <a href="{{ route('admin.deliveries.index') }}" class="nav-link {{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}">
                <i class='bx bx-package'></i><span>Livraisons</span>
            </a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="{{ url('/') }}" class="nav-link"><i class='bx bx-arrow-back'></i><span>Retour au site</span></a>
            <a href="{{ url('/logout') }}" class="nav-link"><i class='bx bx-log-out'></i><span>Déconnexion</span></a>
        </div>
    </nav>

    <div class="admin-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
