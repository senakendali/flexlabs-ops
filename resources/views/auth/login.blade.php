<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FlexOps</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container-fluid login-shell">
        <div class="row justify-content-center align-items-center w-100 mx-0 min-vh-100">
            <div class="col-12 col-xl-11 px-0">
                <div class="login-card">
                    <div class="row g-0 h-100">
                        <div class="col-12 col-lg-6">
                            <div class="login-brand-panel h-100">
                               
                                <div class="brand-content">
                                    <div class="brand-logo brand-logo-top">
                                        <img
                                            src="{{ asset('images/logo.png') }}"
                                            alt="FlexLabs Logo"
                                            width="180"
                                            class="img-fluid"
                                        >

                                        
                                    </div>

                                    <div class="brand-main">
                                        <span class="brand-badge">Internal System</span>

                                        <div class="brand-copy">
                                            <h2>Manage operations with clarity and speed.</h2>
                                            <p>
                                                Sign in to access FlexLabs internal operations dashboard
                                                and manage your daily workflow in one place.
                                            </p>
                                        </div>

                                        <div class="brand-points">
                                            <div class="brand-point-item">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>Program, instructor, trial, sales, and schedule management</span>
                                            </div>
                                            <div class="brand-point-item">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>Centralized participant and equipment tracking</span>
                                            </div>
                                            <div class="brand-point-item">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>Built for smooth daily operational workflow</span>
                                            </div>
                                        </div>

                                        
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="login-form-panel h-100">
                                <div class="form-panel-inner">
                                    <div class="form-box w-100">
                                        <div class="form-header text-center">
                                            <h3>Welcome</h3>
                                            <p>Login in to your account to continue</p>
                                        </div>

                                        @if (session('status'))
                                            <div class="alert-success">
                                                <i class="bi bi-check-circle-fill"></i>
                                                <span>{{ session('status') }}</span>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('login') }}" class="login-form">
                                            @csrf

                                            <div class="form-group">
                                                <div class="input-wrapper pill-input">
                                                    <span class="input-icon">
                                                        <i class="bi bi-envelope"></i>
                                                    </span>
                                                    <input
                                                        id="email"
                                                        type="email"
                                                        name="email"
                                                        value="{{ old('email') }}"
                                                        required
                                                        autofocus
                                                        autocomplete="username"
                                                        placeholder="Email"
                                                        class="form-control custom-input"
                                                    >
                                                </div>
                                                @error('email')
                                                    <div class="error-text">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <div class="input-wrapper pill-input">
                                                    <span class="input-icon">
                                                        <i class="bi bi-lock"></i>
                                                    </span>

                                                    <input
                                                        id="password"
                                                        type="password"
                                                        name="password"
                                                        required
                                                        autocomplete="current-password"
                                                        placeholder="Password"
                                                        class="form-control custom-input pe-5"
                                                    >

                                                    <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Toggle password">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                                @error('password')
                                                    <div class="error-text">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <button type="submit" class="login-button">
                                                <span>Log In</span>
                                            </button>

                                            <div class="form-meta centered-meta">
                                                <label class="remember-me">
                                                    <input id="remember_me" type="checkbox" name="remember">
                                                    <span>Remember me</span>
                                                </label>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>