<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\Auth;

use PHPageBuilder\Contracts\AuthContract;

/**
 * Class Auth
 *
 * A fully object-oriented authentication handler for PHPageBuilder.
 * This class is responsible for:
 * - Login handling
 * - Logout handling
 * - Session authentication
 * - Redirect responses
 * - Input validation
 * - Login form rendering
 */
class Auth implements AuthContract
{
    /**
     * Session key used for authentication.
     */
    private const SESSION_KEY = 'phpb_logged_in';

    /**
     * Allowed authentication actions.
     */
    private const ACTION_LOGIN  = 'login';
    private const ACTION_LOGOUT = 'logout';

    /**
     * Config username.
     *
     * @var string
     */
    protected string $configUsername;

    /**
     * Config password.
     *
     * @var string
     */
    protected string $configPassword;

    /**
     * Auth constructor.
     */
    public function __construct()
    {
        $this->configUsername = (string) phpb_config('auth.username');
        $this->configPassword = (string) phpb_config('auth.password');

        $this->initializeSession();
    }

    /**
     * Initialize session safely.
     */
    protected function initializeSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Main request handler.
     *
     * @param string $action
     *
     * @return void
     */
    public function handleRequest($action): void
    {
        if (!$this->isAuthModuleEnabled()) {
            return;
        }

        switch ($action) {

            case self::ACTION_LOGIN:
                $this->handleLogin();
                break;

            case self::ACTION_LOGOUT:
                $this->handleLogout();
                break;

            default:
                $this->handleUnknownAction();
                break;
        }
    }

    /**
     * Check whether auth module is enabled.
     *
     * @return bool
     */
    protected function isAuthModuleEnabled(): bool
    {
        return phpb_in_module('auth');
    }

    /**
     * Handle login request.
     *
     * @return void
     */
    protected function handleLogin(): void
    {
        if (!$this->isLoginRequestValid()) {
            $this->redirectWithWarning(
                phpb_trans('auth.invalid-request')
            );

            return;
        }

        $credentials = $this->getLoginCredentials();

        if ($this->attemptAuthentication(
            $credentials['username'],
            $credentials['password']
        )) {
            $this->loginUser();

            return;
        }

        $this->redirectWithWarning(
            phpb_trans('auth.invalid-credentials')
        );
    }

    /**
     * Handle logout request.
     *
     * @return void
     */
    protected function handleLogout(): void
    {
        $this->logoutUser();

        phpb_redirect(
            phpb_url('website_manager')
        );
    }

    /**
     * Handle unsupported actions.
     *
     * @return void
     */
    protected function handleUnknownAction(): void
    {
        phpb_redirect(
            phpb_url('website_manager'),
            [
                'message-type' => 'warning',
                'message'      => 'Unknown authentication action.'
            ]
        );
    }

    /**
     * Validate login request.
     *
     * @return bool
     */
    protected function isLoginRequestValid(): bool
    {
        return isset($_POST['username'], $_POST['password']);
    }

    /**
     * Get sanitized credentials.
     *
     * @return array<string, string>
     */
    protected function getLoginCredentials(): array
    {
        return [
            'username' => $this->sanitizeInput($_POST['username']),
            'password' => $this->sanitizeInput($_POST['password']),
        ];
    }

    /**
     * Sanitize user input.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function sanitizeInput($value): string
    {
        return trim((string) $value);
    }

    /**
     * Attempt authentication.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function attemptAuthentication(
        string $username,
        string $password
    ): bool {
        return $this->validateUsername($username)
            && $this->validatePassword($password);
    }

    /**
     * Validate username.
     *
     * @param string $username
     *
     * @return bool
     */
    protected function validateUsername(string $username): bool
    {
        return hash_equals(
            $this->configUsername,
            $username
        );
    }

    /**
     * Validate password.
     *
     * @param string $password
     *
     * @return bool
     */
    protected function validatePassword(string $password): bool
    {
        return hash_equals(
            $this->configPassword,
            $password
        );
    }

    /**
     * Login user and regenerate session.
     *
     * @return void
     */
    protected function loginUser(): void
    {
        session_regenerate_id(true);

        $_SESSION[self::SESSION_KEY] = true;

        phpb_redirect(
            phpb_url('website_manager')
        );
    }

    /**
     * Logout authenticated user.
     *
     * @return void
     */
    protected function logoutUser(): void
    {
        unset($_SESSION[self::SESSION_KEY]);

        session_regenerate_id(true);
    }

    /**
     * Redirect with warning message.
     *
     * @param string $message
     *
     * @return void
     */
    protected function redirectWithWarning(string $message): void
    {
        phpb_redirect(
            phpb_url('website_manager'),
            [
                'message-type' => 'warning',
                'message'      => $message,
            ]
        );
    }

    /**
     * Determine whether current user is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_KEY])
            && $_SESSION[self::SESSION_KEY] === true;
    }

    /**
     * Require authentication before continuing.
     *
     * @return void
     */
    public function requireAuth(): void
    {
        if ($this->isAuthenticated()) {
            return;
        }

        $this->showGuestView();
    }

    /**
     * Show guest view.
     *
     * @return void
     */
    protected function showGuestView(): void
    {
        $this->renderLoginForm();

        exit;
    }

    /**
     * Render login form view.
     *
     * @return void
     */
    public function renderLoginForm(): void
    {
        $viewFile = 'login-form';

        require $this->getLayoutPath();
    }

    /**
     * Get layout file path.
     *
     * @return string
     */
    protected function getLayoutPath(): string
    {
        return __DIR__ . '/resources/views/layout.php';
    }

    /**
     * Check whether user session exists.
     *
     * @return bool
     */
    public function hasSession(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Destroy current session completely.
     *
     * @return void
     */
    public function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Force user logout completely.
     *
     * @return void
     */
    public function forceLogout(): void
    {
        $this->destroySession();

        phpb_redirect(
            phpb_url('website_manager')
        );
    }

    /**
     * Get authenticated user information.
     *
     * @return array<string, mixed>
     */
    public function getUser(): array
    {
        if (!$this->isAuthenticated()) {
            return [];
        }

        return [
            'username' => $this->configUsername,
            'logged_in' => true,
        ];
    }

    /**
     * Magic method for debugging.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'authenticated' => $this->isAuthenticated(),
            'session_key'   => self::SESSION_KEY,
            'module_loaded' => $this->isAuthModuleEnabled(),
        ];
    }
}
