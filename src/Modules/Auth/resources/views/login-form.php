<?php
// Optional: preserve submitted username after a validation error.
$username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <span
                                class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle"
                                style="width: 64px; height: 64px;"
                                aria-hidden="true"
                            >
                                <i class="bi bi-person-lock fs-3"></i>
                            </span>
                        </div>

                        <h1 class="h3 fw-bold mb-2">
                            <?= htmlspecialchars(phpb_trans('auth.login-title') ?? 'Welcome Back', ENT_QUOTES, 'UTF-8') ?>
                        </h1>

                        <p class="text-muted mb-0">
                            <?= htmlspecialchars(
                                phpb_trans('auth.login-subtitle') ?? 'Sign in to continue to your account.',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    </div>

                    <form
                        method="post"
                        action=""
                        id="loginForm"
                        autocomplete="on"
                        novalidate
                    >

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">
                                <?= htmlspecialchars(
                                    phpb_trans('auth.username') ?? 'Username',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </label>

                            <div class="input-group input-group-lg">
                                <span class="input-group-text" aria-hidden="true">
                                    <i class="bi bi-person"></i>
                                </span>

                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="form-control"
                                    placeholder="<?= htmlspecialchars(
                                        phpb_trans('auth.username') ?? 'Username',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                                    autocomplete="username"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    minlength="2"
                                    required
                                    autofocus
                                >
                            </div>

                            <div class="invalid-feedback">
                                <?= htmlspecialchars(
                                    phpb_trans('auth.username-required') ?? 'Please enter your username.',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                <?= htmlspecialchars(
                                    phpb_trans('auth.password') ?? 'Password',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </label>

                            <div class="input-group input-group-lg">
                                <span class="input-group-text" aria-hidden="true">
                                    <i class="bi bi-lock"></i>
                                </span>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="<?= htmlspecialchars(
                                        phpb_trans('auth.password') ?? 'Password',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    autocomplete="current-password"
                                    minlength="1"
                                    required
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="togglePassword"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <i class="bi bi-eye" id="passwordIcon"></i>
                                </button>
                            </div>

                            <div class="invalid-feedback">
                                <?= htmlspecialchars(
                                    phpb_trans('auth.password-required') ?? 'Please enter your password.',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    id="remember"
                                    autocomplete="off"
                                >

                                <label class="form-check-label" for="remember">
                                    <?= htmlspecialchars(
                                        phpb_trans('auth.remember-me') ?? 'Remember me',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </label>
                            </div>

                            <a
                                href="/forgot-password"
                                class="text-decoration-none fw-semibold"
                            >
                                <?= htmlspecialchars(
                                    phpb_trans('auth.forgot-password') ?? 'Forgot password?',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </a>
                        </div>

                        <button
                            class="btn btn-primary btn-lg w-100 fw-semibold d-flex align-items-center justify-content-center gap-2"
                            type="submit"
                            id="loginButton"
                        >
                            <span
                                class="spinner-border spinner-border-sm d-none"
                                id="loginSpinner"
                                aria-hidden="true"
                            ></span>

                            <i class="bi bi-box-arrow-in-right" id="loginIcon"></i>

                            <span id="loginButtonText">
                                <?= htmlspecialchars(
                                    phpb_trans('auth.login-button') ?? 'Sign In',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </button>

                        <div
                            class="alert alert-danger d-none mt-3 mb-0"
                            id="loginError"
                            role="alert"
                        ></div>
                    </form>
                </div>
            </div>

            <p class="text-center text-muted small mt-4 mb-0">
                &copy; <?= date('Y') ?>
                <?= htmlspecialchars(
                    phpb_trans('app.name') ?? 'Your Application',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const form = document.getElementById('loginForm');
    const username = document.getElementById('username');
    const password = document.getElementById('password');

    const togglePassword = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    const loginButton = document.getElementById('loginButton');
    const loginSpinner = document.getElementById('loginSpinner');
    const loginIcon = document.getElementById('loginIcon');
    const loginButtonText = document.getElementById('loginButtonText');
    const loginError = document.getElementById('loginError');

    /*
     * Password visibility toggle
     */
    togglePassword?.addEventListener('click', function () {
        const isPassword = password.type === 'password';

        password.type = isPassword ? 'text' : 'password';

        passwordIcon.className = isPassword
            ? 'bi bi-eye-slash'
            : 'bi bi-eye';

        togglePassword.setAttribute(
            'aria-label',
            isPassword ? 'Hide password' : 'Show password'
        );

        togglePassword.setAttribute(
            'aria-pressed',
            String(isPassword)
        );
    });

    /*
     * Remove validation state while typing
     */
    [username, password].forEach(function (field) {
        field?.addEventListener('input', function () {
            field.classList.remove('is-invalid');
        });
    });

    /*
     * Login form validation + loading state
     */
    form?.addEventListener('submit', function (event) {
        loginError.classList.add('d-none');
        loginError.textContent = '';

        let valid = true;

        if (!username.value.trim()) {
            username.classList.add('is-invalid');
            valid = false;
        }

        if (!password.value) {
            password.classList.add('is-invalid');
            valid = false;
        }

        if (!valid) {
            event.preventDefault();

            const firstInvalid = form.querySelector('.is-invalid');

            if (firstInvalid) {
                firstInvalid.focus();
            }

            return;
        }

        /*
         * Prevent accidental double submissions.
         */
        loginButton.disabled = true;

        loginSpinner.classList.remove('d-none');
        loginIcon.classList.add('d-none');

        loginButtonText.textContent = 'Signing in...';
    });

})();
</script>
