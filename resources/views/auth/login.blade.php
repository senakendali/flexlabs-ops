<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FlexOps</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand-panel">
                <div class="brand-overlay"></div>

                <div class="brand-content">
                    <div class="brand-logo">
                        <div class="brand-logo-icon">
                            <i class="bi bi-grid-1x2-fill"></i>
                        </div>
                        <div class="brand-logo-text">
                            <h1>FlexOps</h1>
                            <p>Operations Platform</p>
                        </div>
                    </div>

                    

                    

                    <div class="brand-footer">
                        <span class="brand-footer-dot"></span>
                        <span>ops.flexlabs.co.id</span>
                    </div>
                </div>
            </div>

            <div class="login-form-panel">
                <div class="form-box">
                    <div class="form-header">
                        <span class="form-badge">Sign In</span>
                        <h3>Welcome back</h3>
                        <p>Login to continue to FlexOps.</p>
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
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
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
                                    placeholder="Enter your email"
                                >
                            </div>
                            @error('email')
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon">
                                    <i class="bi bi-lock"></i>
                                </span>

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                >

                                <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Toggle password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-meta">
                            <label class="remember-me">
                                <input id="remember_me" type="checkbox" name="remember">
                                <span>Remember me</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a class="forgot-link" href="{{ route('password.request') }}">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="login-button">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Sign In</span>
                        </button>
                    </form>

                    <div class="form-footer">
                        <p>FlexLabs Internal Access</p>
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