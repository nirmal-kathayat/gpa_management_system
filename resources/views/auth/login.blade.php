<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GPA Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
</head>

<body>
    <div class="auth-wrapper">
        <section class="auth-visual">
            <div class="brand">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <div class="brand-name">GPA Management System</div>
                    <div class="brand-tagline">Track Your Grades. Build a Brighter Future.</div>
                </div>
            </div>

            <div class="visual-copy">
                <h1>Education Today<br>A Brighter Tomorrow</h1>
                <p>Manage your GPA, track your academic performance and achieve your goals.</p>
            </div>

            <figure class="quote-card">
                <i class="fas fa-quote-left"></i>
                <div>
                    <p class="quote-text">Small progress each day leads to big results.</p>
                    <figcaption class="quote-author">&mdash; Keep Going</figcaption>
                </div>
            </figure>
        </section>

        <section class="auth-panel">
            <div class="auth-form">
                <h2>Welcome Back</h2>
                <p class="subtitle">Sign in to continue to your account</p>

                @if (session('status'))
                    <div class="alert-flash">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="field">
                        <div class="input-shell @error('username') is-invalid @enderror">
                            <span class="leading"><i class="fas fa-user"></i></span>
                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                   placeholder="Username or Email" required autofocus autocomplete="username">
                        </div>
                        @error('username')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <div class="input-shell @error('password') is-invalid @enderror">
                            <span class="leading"><i class="fas fa-lock"></i></span>
                            <input type="password" id="password" name="password"
                                   placeholder="Password" required autocomplete="current-password">
                            <button type="button" class="trailing" id="togglePassword"
                                    aria-label="Show password">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="options-row">
                        <label class="remember">
                            <input type="checkbox" name="remember" value="1"
                                   {{ old('remember', true) ? 'checked' : '' }}>
                            Remember me
                        </label>
                        <a href="#" class="forgot">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-signin">
                        Sign In <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="divider">or</div>

                <div class="info-box">
                    <i class="fas fa-users"></i>
                    <div>
                        <div class="info-title">New here?</div>
                        <div class="info-text">Contact your administrator for access.</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('togglePassword');
            const input = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');

            toggle.addEventListener('click', function () {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
</body>

</html>
