<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FlexLabs')</title>

    <meta name="description" content="Trial Class FlexLabs">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --primary: #5B3E8E;
            --primary-dark: #452f6b;
            --secondary: #FFBE04;
            --soft-bg: #f7f5fc;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-soft: rgba(91, 62, 142, 0.12);
            --shadow-soft: 0 20px 60px rgba(91, 62, 142, 0.12);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255, 190, 4, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(91, 62, 142, 0.12), transparent 30%),
                linear-gradient(180deg, #ffffff 0%, var(--soft-bg) 100%);
            color: var(--text-main);
            min-height: 100vh;
        }

        .public-navbar {
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(91, 62, 142, 0.08);
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .brand-logo img{
            width:180px;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), #7a55bc);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            box-shadow: 0 12px 28px rgba(91, 62, 142, 0.28);
        }

        .nav-public-link {
            color: var(--text-main);
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .nav-public-link:hover {
            color: var(--primary);
        }

        .hero-section {
            padding: 4rem 0 2.5rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(91, 62, 142, 0.08);
            color: var(--primary);
            border: 1px solid rgba(91, 62, 142, 0.10);
            border-radius: 999px;
            padding: 0.5rem 0.9rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .hero-title {
            font-size: clamp(2.2rem, 4vw, 4.3rem);
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .hero-title .highlight {
            color: var(--primary);
        }

        .hero-desc {
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--text-muted);
            max-width: 650px;
        }

        .hero-card {
            background: rgba(255, 255, 255, 0.90);
            border: 1px solid var(--border-soft);
            border-radius: 28px;
            padding: 1.5rem;
            box-shadow: var(--shadow-soft);
        }

        .hero-stat {
            background: linear-gradient(180deg, #ffffff 0%, #faf7ff 100%);
            border: 1px solid rgba(91, 62, 142, 0.10);
            border-radius: 20px;
            padding: 1rem;
            height: 100%;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
        }

        .hero-stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }

        .content-section {
            padding-bottom: 4rem;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--border-soft);
            border-radius: 28px;
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .section-card-header {
            padding: 1.5rem 1.5rem 0;
        }

        .section-title {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }

        .section-subtitle {
            color: var(--text-muted);
            margin-bottom: 0;
        }

        .section-card-body {
            padding: 1.5rem;
        }

        .feature-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 0.95rem;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(91, 62, 142, 0.12);
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.92rem;
        }

        .btn-brand {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            font-weight: 600;
            border-radius: 14px;
            padding: 0.9rem 1.2rem;
        }

        .btn-brand:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #fff;
        }

        .btn-soft {
            background: #fff;
            border: 1px solid rgba(91, 62, 142, 0.14);
            color: var(--primary);
            font-weight: 600;
            border-radius: 14px;
            padding: 0.9rem 1.2rem;
        }

        .btn-soft:hover {
            background: #f8f5ff;
            color: var(--primary);
        }

        .about-card {
            background: linear-gradient(135deg, rgba(91, 62, 142, 0.06), rgba(255, 190, 4, 0.10));
            border: 1px solid rgba(91, 62, 142, 0.10);
            border-radius: 22px;
            padding: 1.25rem;
        }

        .public-footer {
            padding: 2rem 0 3rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            border-radius: 14px;
            border-color: rgba(91, 62, 142, 0.15);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(91, 62, 142, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(91, 62, 142, 0.12);
        }

        .toast-container {
            z-index: 1080;
        }

        .success-state {
            display: none;
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.08), rgba(255, 255, 255, 0.95));
            border: 1px solid rgba(25, 135, 84, 0.14);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
        }

        .success-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(25, 135, 84, 0.10);
            color: #198754;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 991.98px) {
            .hero-section {
                padding-top: 2.5rem;
            }

            .hero-card,
            .section-card {
                border-radius: 22px;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <header class="public-navbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a href="{{ url('/trial-class') }}" class="brand-logo">
                    <img src="{{ asset('images/logo-black.png') }}" alt="Logo" class="dashboard-logo me-2">
                </a>

                <nav class="d-flex align-items-center gap-4">
                    <a href="https://flexlabs.co.id" class="nav-public-link">
                       
                        About FlexLabs
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="public-footer">
        <div class="container text-center">
            <div>© {{ date('Y') }} FlexLabs. Dibangun untuk pembelajar yang siap naik level.</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>