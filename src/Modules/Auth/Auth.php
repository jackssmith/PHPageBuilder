<?php

namespace PHPageBuilder\Modules\Auth;

use PHPageBuilder\Contracts\AuthContract;

class Auth implements AuthContract
{
    /**
     * Process the current GET or POST request and redirect or render the requested page.
     *
     * @param string $action
     */
    public function handleRequest($action)
    {
        if (!phpb_in_module('auth')) {
            return;
        }

        switch ($action) {

            case 'login':
                if (!isset($_POST['username'], $_POST['password'])) {
                    return;
                }

                $username = trim($_POST['username']);
                $password = trim($_POST['password']);

                $configUser = phpb_config('auth.username');
                $configPass = phpb_config('auth.password');

                if (hash_equals($configUser, $username) && hash_equals($configPass, $password)) {
                    session_regenerate_id(true);
                    $_SESSION['phpb_logged_in'] = true;

                    phpb_redirect(phpb_url('website_manager'));
                    return;
                }

                phpb_redirect(
                    phpb_url('website_manager'),
                    [
                        'message-type' => 'warning',
                        'message' => phpb_trans('auth.invalid-credentials')
                    ]
                );
                return;

            case 'logout':
                unset($_SESSION['phpb_logged_in']);
                session_regenerate_id(true);

                phpb_redirect(phpb_url('website_manager'));
                return;
        }
    }

    /**
     * Return whether the current request has an authenticated session.
     */
    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['phpb_logged_in']) && $_SESSION['phpb_logged_in'] === true;
    }

    /**
     * If the user is not authenticated, show the login form.
     */
    public function requireAuth(): void
    {
        if ($this->isAuthenticated()) {
            return;
        }

        $this->renderLoginForm();
        exit;
    }

    /**
     * Render the login form.
     */
    public function renderLoginForm(): void
    {
        $viewFile = 'login-form';
        require __DIR__ . '/resources/views/layout.php';
    }
}
