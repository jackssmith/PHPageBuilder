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
            self::$settings[$setting['setting']] = $setting['is_array']
                ? explode(',', $setting['value'])
                : $setting['value'];
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
     * Get the value of a setting.
     *
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key)
    {
        self::ensureLoaded();

        return self::$settings[$key] ?? null;
    }

    /**
     * Determine whether the given setting exists and matches the given value.
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public static function has(string $key, string $value): bool
    {
        self::ensureLoaded();

        if (!isset(self::$settings[$key])) {
            return false;
        }

        $setting = self::$settings[$key];

        return is_array($setting)
            ? in_array($value, $setting, true)
            : $setting === $value;
    }
}
