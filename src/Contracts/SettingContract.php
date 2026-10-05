<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

interface SettingContract
{
    /**
     * Get a setting value by key.
     *
     * Returns null when the setting does not exist.
     *
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key);

    /**
     * Get a setting value or return a default value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getOr(string $key, mixed $default = null): mixed;

    /**
     * Determine whether a setting exists.
     *
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool;

    /**
     * Determine whether a setting exists and matches a value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function has(string $key, mixed $value): bool;

    /**
     * Get all settings.
     *
     * @return array<string, mixed>
     */
    public static function all(): array;

    /**
     * Set a setting value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void;

    /**
     * Remove a setting.
     *
     * @param string $key
     * @return bool
     */
    public static function remove(string $key): bool;

    /**
     * Alias for remove().
     *
     * @param string $key
     * @return bool
     */
    public static function forget(string $key): bool;

    /**
     * Remove all settings.
     *
     * @return void
     */
    public static function clear(): void;
}
