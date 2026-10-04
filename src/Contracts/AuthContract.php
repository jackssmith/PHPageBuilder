<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authentication contract for PHPageBuilder.
 *
 * This contract defines the common authentication operations required by
 * an application, including:
 *
 * - Login / authentication attempts
 * - Logout
 * - Session management
 * - Remember-me authentication
 * - Authentication checks
 * - User retrieval
 * - Guest/authenticated middleware responses
 * - Password verification
 * - Password reset support
 * - Email verification support
 * - Two-factor authentication hooks
 * - Role and permission checks
 *
 * Implementations are free to use sessions, tokens, cookies, JWTs,
 * database-backed authentication, or another authentication mechanism.
 */
interface AuthContract
{
    /**
     * Handle an authentication-related HTTP request.
     *
     * Common actions may include:
     *
     * - login
     * - logout
     * - register
     * - forgot-password
     * - reset-password
     * - verify-email
     * - resend-verification
     * - enable-2fa
     * - disable-2fa
     *
     * @param ServerRequestInterface $request
     * @param string|null $action Optional authentication action.
     *
     * @return ResponseInterface
     */
    public function handleRequest(
        ServerRequestInterface $request,
        ?string $action = null
    ): ResponseInterface;

    /**
     * Determine whether the current request is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Determine whether the current request is unauthenticated.
     *
     * This is generally the inverse of isAuthenticated(), but is provided
     * for readability when implementing guest-only routes.
     *
     * @return bool
     */
    public function isGuest(): bool;

    /**
     * Require an authenticated user.
     *
     * Implementations may redirect to the login page, return a JSON
     * response, or use another appropriate authentication response.
     *
     * @return ResponseInterface
     */
    public function requireAuth(): ResponseInterface;

    /**
     * Require the current visitor to be unauthenticated.
     *
     * Useful for login, registration, and password-reset pages.
     *
     * @return ResponseInterface
     */
    public function requireGuest(): ResponseInterface;

    /**
     * Render the login form.
     *
     * @return ResponseInterface
     */
    public function renderLoginForm(): ResponseInterface;

    /**
     * Render the registration form.
     *
     * @return ResponseInterface
     */
    public function renderRegistrationForm(): ResponseInterface;

    /**
     * Render the password-forgotten form.
     *
     * @return ResponseInterface
     */
    public function renderForgotPasswordForm(): ResponseInterface;

    /**
     * Render the password-reset form.
     *
     * @param string|null $token Password reset token, if available.
     *
     * @return ResponseInterface
     */
    public function renderResetPasswordForm(
        ?string $token = null
    ): ResponseInterface;

    /**
     * Render the email-verification page.
     *
     * @return ResponseInterface
     */
    public function renderVerificationForm(): ResponseInterface;

    /**
     * Attempt to authenticate a user using the provided credentials.
     *
     * Typical credentials may include:
     *
     * - email
     * - username
     * - password
     * - remember
     * - otp
     *
     * @param array<string, mixed> $credentials
     *
     * @return bool
     */
    public function attempt(array $credentials): bool;

    /**
     * Authenticate a user directly.
     *
     * This method should establish the authenticated state/session.
     *
     * @param mixed $user
     * @param bool $remember Whether the authentication should persist.
     *
     * @return void
     */
    public function login(
        mixed $user,
        bool $remember = false
    ): void;

    /**
     * Log out the currently authenticated user.
     *
     * Implementations should invalidate the current authentication state.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Completely invalidate the current authentication session.
     *
     * This may additionally invalidate remember-me credentials,
     * authentication tokens, or other persistent credentials.
     *
     * @return void
     */
    public function invalidate(): void;

    /**
     * Get the currently authenticated user.
     *
     * @return mixed|null
     */
    public function user(): mixed;

    /**
     * Get the currently authenticated user's identifier.
     *
     * @return int|string|null
     */
    public function id(): int|string|null;

    /**
     * Check whether the authenticated user has a specific role.
     *
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool;

    /**
     * Check whether the authenticated user has any of the supplied roles.
     *
     * @param array<int, string> $roles
     *
     * @return bool
     */
    public function hasAnyRole(array $roles): bool;

    /**
     * Check whether the authenticated user has all supplied roles.
     *
     * @param array<int, string> $roles
     *
     * @return bool
     */
    public function hasAllRoles(array $roles): bool;

    /**
     * Determine whether the authenticated user has a permission.
     *
     * @param string $permission
     *
     * @return bool
     */
    public function can(string $permission): bool;

    /**
     * Determine whether the authenticated user cannot perform a permission.
     *
     * @param string $permission
     *
     * @return bool
     */
    public function cannot(string $permission): bool;

    /**
     * Determine whether the current user has any of the supplied permissions.
     *
     * @param array<int, string> $permissions
     *
     * @return bool
     */
    public function canAny(array $permissions): bool;

    /**
     * Determine whether the current user has all supplied permissions.
     *
     * @param array<int, string> $permissions
     *
     * @return bool
     */
    public function canAll(array $permissions): bool;

    /**
     * Register a new user.
     *
     * The implementation is responsible for validation, password hashing,
     * persistence, and any required verification workflow.
     *
     * @param array<string, mixed> $data
     *
     * @return mixed The newly registered user.
     */
    public function register(array $data): mixed;

    /**
     * Send a password-reset link to a user.
     *
     * @param string $identifier Email address, username, or another
     *                           supported account identifier.
     *
     * @return bool
     */
    public function sendPasswordResetLink(string $identifier): bool;

    /**
     * Reset a user's password using a reset token.
     *
     * @param string $token
     * @param string $password
     * @param string|null $passwordConfirmation
     *
     * @return bool
     */
    public function resetPassword(
        string $token,
        string $password,
        ?string $passwordConfirmation = null
    ): bool;

    /**
     * Change the currently authenticated user's password.
     *
     * @param string $currentPassword
     * @param string $newPassword
     * @param string|null $newPasswordConfirmation
     *
     * @return bool
     */
    public function changePassword(
        string $currentPassword,
        string $newPassword,
        ?string $newPasswordConfirmation = null
    ): bool;

    /**
     * Verify a user's password.
     *
     * @param string $password
     *
     * @return bool
     */
    public function verifyPassword(string $password): bool;

    /**
     * Determine whether the current user's email address is verified.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool;

    /**
     * Send an email verification notification.
     *
     * @return bool
     */
    public function sendVerificationEmail(): bool;

    /**
     * Verify a user's email address.
     *
     * @param string $token
     *
     * @return bool
     */
    public function verifyEmail(string $token): bool;

    /**
     * Determine whether two-factor authentication is enabled.
     *
     * @return bool
     */
    public function hasTwoFactorEnabled(): bool;

    /**
     * Enable two-factor authentication.
     *
     * @param string $code Verification code or setup confirmation code.
     *
     * @return bool
     */
    public function enableTwoFactor(string $code): bool;

    /**
     * Disable two-factor authentication.
     *
     * @param string $password Current account password for confirmation.
     *
     * @return bool
     */
    public function disableTwoFactor(string $password): bool;

    /**
     * Verify a two-factor authentication code.
     *
     * @param string $code
     *
     * @return bool
     */
    public function verifyTwoFactor(string $code): bool;

    /**
     * Determine whether the current authentication attempt requires
     * two-factor verification.
     *
     * @return bool
     */
    public function requiresTwoFactor(): bool;

    /**
     * Determine whether the current session was authenticated using
     * a persistent "remember me" credential.
     *
     * @return bool
     */
    public function isRemembered(): bool;

    /**
     * Remember the current authenticated user.
     *
     * @return void
     */
    public function remember(): void;

    /**
     * Forget the current persistent authentication credential.
     *
     * @return void
     */
    public function forget(): void;

    /**
     * Regenerate the authentication/session identifier.
     *
     * This should normally be called after authentication to help
     * prevent session fixation.
     *
     * @return void
     */
    public function regenerateSession(): void;

    /**
     * Get the current authentication/session identifier.
     *
     * @return string|null
     */
    public function sessionId(): ?string;

    /**
     * Determine whether the current authentication session is valid.
     *
     * @return bool
     */
    public function validateSession(): bool;

    /**
     * Refresh the current authentication session.
     *
     * This can be used to extend a session lifetime or rotate
     * authentication credentials.
     *
     * @return bool
     */
    public function refreshSession(): bool;

    /**
     * Determine whether the current authentication state has expired.
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Get the remaining authentication/session lifetime in seconds.
     *
     * @return int|null Null when the lifetime is not applicable or unknown.
     */
    public function sessionLifetime(): ?int;

    /**
     * Get the authentication guard name.
     *
     * @return string
     */
    public function guard(): string;

    /**
     * Set or switch the authentication guard.
     *
     * @param string $guard
     *
     * @return static
     */
    public function setGuard(string $guard): static;

    /**
     * Get the configured login URL/path.
     *
     * @return string
     */
    public function loginUrl(): string;

    /**
     * Get the configured logout URL/path.
     *
     * @return string
     */
    public function logoutUrl(): string;

    /**
     * Get the configured registration URL/path.
     *
     * @return string
     */
    public function registrationUrl(): string;

    /**
     * Get the configured password-reset URL/path.
     *
     * @return string
     */
    public function passwordResetUrl(): string;

    /**
     * Get the configured email-verification URL/path.
     *
     * @return string
     */
    public function verificationUrl(): string;

    /**
     * Redirect an unauthenticated visitor to the login page.
     *
     * @param string|null $intendedUrl URL to return to after authentication.
     *
     * @return ResponseInterface
     */
    public function redirectToLogin(
        ?string $intendedUrl = null
    ): ResponseInterface;

    /**
     * Redirect an authenticated user to an appropriate destination.
     *
     * @param string|null $url Optional destination URL.
     *
     * @return ResponseInterface
     */
    public function redirectAfterLogin(
        ?string $url = null
    ): ResponseInterface;

    /**
     * Redirect a user after logout.
     *
     * @param string|null $url Optional destination URL.
     *
     * @return ResponseInterface
     */
    public function redirectAfterLogout(
        ?string $url = null
    ): ResponseInterface;

    /**
     * Get the URL the user originally attempted to access.
     *
     * @return string|null
     */
    public function intendedUrl(): ?string;

    /**
     * Store an intended URL for redirecting after authentication.
     *
     * @param string $url
     *
     * @return void
     */
    public function setIntendedUrl(string $url): void;

    /**
     * Clear the stored intended URL.
     *
     * @return void
     */
    public function clearIntendedUrl(): void;

    /**
     * Determine whether the current user is authorized for an action.
     *
     * @param string $ability
     * @param mixed|null $resource Optional resource being authorized.
     *
     * @return bool
     */
    public function authorize(
        string $ability,
        mixed $resource = null
    ): bool;

    /**
     * Require a specific role.
     *
     * @param string $role
     *
     * @return ResponseInterface
     */
    public function requireRole(string $role): ResponseInterface;

    /**
     * Require a specific permission.
     *
     * @param string $permission
     *
     * @return ResponseInterface
     */
    public function requirePermission(string $permission): ResponseInterface;

    /**
     * Get the current authentication error message.
     *
     * @return string|null
     */
    public function error(): ?string;

    /**
     * Get all authentication errors.
     *
     * @return array<string, mixed>
     */
    public function errors(): array;

    /**
     * Clear authentication errors.
     *
     * @return void
     */
    public function clearErrors(): void;

    /**
     * Determine whether the authentication system currently
     * has an authentication error.
     *
     * @return bool
     */
    public function hasError(): bool;

    /**
     * Determine whether a login attempt is currently in progress.
     *
     * Useful for multi-step authentication such as OTP or 2FA.
     *
     * @return bool
     */
    public function isAuthenticating(): bool;

    /**
     * Determine whether the current user is locked out.
     *
     * @return bool
     */
    public function isLockedOut(): bool;

    /**
     * Get the remaining account lockout duration in seconds.
     *
     * @return int|null
     */
    public function lockoutRemaining(): ?int;

    /**
     * Determine whether login throttling is active.
     *
     * @return bool
     */
    public function isThrottled(): bool;

    /**
     * Get the number of failed authentication attempts.
     *
     * @return int
     */
    public function failedAttempts(): int;

    /**
     * Reset failed authentication attempts.
     *
     * @return void
     */
    public function resetFailedAttempts(): void;

    /**
     * Get the authentication state as an array.
     *
     * Useful for APIs, debugging, middleware, and application-level
     * authentication state inspection.
     *
     * @return array<string, mixed>
     */
    public function state(): array;

    /**
     * Determine whether the authentication system supports a feature.
     *
     * Examples:
     *
     * - remember
     * - two-factor
     * - password-reset
     * - email-verification
     * - registration
     *
     * @param string $feature
     *
     * @return bool
     */
    public function supports(string $feature): bool;
}
