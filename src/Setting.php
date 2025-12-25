<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\SettingContract;
use PHPageBuilder\Repositories\SettingRepository;

class Setting implements SettingContract
{
    /**
     * Cached settings loaded from the database.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $settings = null;

    /**
     * Load all settings from the database into memory.
     */
    protected static function loadSettings(): void
    {
        self::$settings = [];

        $repository = new SettingRepository();

        // Ensure the repository returns valid data
        $settingsData = $repository->getAll();
        if (empty($settingsData)) {
            return; // No settings to load
        }

        foreach ($settingsData as $setting) {
            $value = $setting['value'];

            // If the value is an array (stored as a comma-separated string), process it
            if ((bool) $setting['is_array']) {
                $value = array_values(
                    array_filter(
                        array_map('trim', explode(',', (string) $value)),
                        static fn($v) => $v !== ''
                    )
                );
            }

            self::$settings[$setting['setting']] = $value;
        }
    }

    /**
     * Ensure settings are loaded into memory.
     */
    protected static function ensureLoaded(): void
    {
        if (self::$settings === null) {
            self::loadSettings();
        }
    }

    /**
     * Reload all settings from the database.
     */
    public static function reload(): void
    {
        self::$settings = null;
        self::ensureLoaded();
    }

    /**
     * Get the value of a setting.
     *
     * @param string $key The setting key.
     * @param mixed $default The default value if the setting doesn't exist.
     * @return mixed The value of the setting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureLoaded();

        // Return setting if exists, otherwise default
        return self::$settings[$key] ?? $default;
    }

    /**
     * Set or override a setting value at runtime.
     *
     * @param string $key The setting key.
     * @param mixed $value The value to set.
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureLoaded();

        self::$settings[$key] = $value;
    }

    /**
     * Determine if the given setting exists.
     *
     * @param string $key The setting key.
     * @return bool True if the setting exists, false otherwise.
     */
    public static function exists(string $key): bool
    {
        self::ensureLoaded();

        return array_key_exists($key, self::$settings);
    }

    /**
     * Determine whether the given setting exists and matches the provided value.
     *
     * @param string $key The setting key.
     * @param mixed $value The value to match.
     * @return bool True if the setting exists and matches, false otherwise.
     */
    public static function has(string $key, mixed $value): bool
    {
        self::ensureLoaded();

        // Check if the setting exists and matches the provided value
        return self::exists($key) && (
            is_array(self::$settings[$key])
                ? in_array($value, self::$settings[$key], true)
                : self::$settings[$key] === $value
        );
    }
}
