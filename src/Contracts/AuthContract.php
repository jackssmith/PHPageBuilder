<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface AuthContract
{
    /**
     * Handle the incoming HTTP request and perform authentication-related actions
     * such as login, logout, or redirecting the user.
     *
     * @param ServerRequestInterface $request
     * @param string|null $action Optional action identifier (e.g. "login", "logout")
     *
     * @return ResponseInterface
     */
    public function handleRequest(
        ServerRequestInterface $request,
        ?string $action = null
    ): ResponseInterface;

    /**
     * Determine whether the current request has an authenticated user session.
     *
     * @return bool True if the user is authenticated, false otherwise
     */
    public function isAuthenticated(): bool;

    /**
     * Ensure the current user is authenticated.
     * If not authenticated, this method should redirect to the login page
     * or render the login form.
     *
     * @return ResponseInterface
     */
    public function requireAuth(): ResponseInterface;

    /**
     * Render and return the login form response.
     *
     * @return ResponseInterface
     */
    public function renderLoginForm(): ResponseInterface;

    /**
     * Attempt to authenticate a user using the provided credentials.
     *
     * @param array<string, mixed> $credentials
     *
     * @return bool True on successful authentication
     */
    public function attempt(array $credentials): bool;

    /**
     * Log out the currently authenticated user and invalidate the session.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Get the currently authenticated user, if any.
     *
     * @return mixed|null The authenticated user or null if not authenticated
     */
    public function user();
}
