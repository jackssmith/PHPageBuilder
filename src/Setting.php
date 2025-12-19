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

        foreach ($repository->getAll() as $setting) {
            $value = $setting['value'];

            if ((bool) $setting['is_array']) {
                $value = array_values(
                    array_filter(
                        array_map('trim', explode(',', (string) $value)),
                        static fn ($v) => $v !== ''
                    )
                );
            }

            self::$settings[$setting['setting']] = $value;
        }
    }

    /**
     * Ensure settings are loaded.
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
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureLoaded();

        return self::$settings[$key] ?? $default;
    }

    /**
     * Set or override a setting value at runtime.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureLoaded();

        self::$settings[$key] = $value;
    }

    /**
     * Determine whether the given setting exists.
     */
    public static function exists(string $key): bool
    {
        self::ensureLoaded();

        return array_key_exists($key, self::$settings);
    }

    /**
     * Determine whether the given setting exists and matches the given value.
     */
    public static function has(string $key, mixed $value): bool
    {
        self::ensureLoaded();

        if (!array_key_exists($key, self::$settings)) {
            return false;
        }

        $setting = self::$settings[$key];

        return is_array($setting)
            ? in_array($value, $setting, true)
            : $setting === $value;
    }
}
