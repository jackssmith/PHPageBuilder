<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\SettingContract;
use PHPageBuilder\Repositories\SettingRepository;
use RuntimeException;

final class Setting implements SettingContract
{
    private array $settings = [];
    private bool $loaded = false;
    private bool $immutable = false;

    public function __construct(
        private readonly SettingRepository $repository
    ) {}

    /**
     * Load settings from repository
     */
    public function load(): void
    {
        $this->settings = [];

        foreach ($this->repository->getAll() as $row) {
            $this->settings[$row['setting']] = $this->castValue(
                $row['value'],
                (bool) $row['is_array']
            );
        }

        $this->loaded = true;
    }

    /**
     * Ensure settings are loaded
     */
    private function ensureLoaded(): void
    {
        if (!$this->loaded) {
            $this->load();
        }
    }

    /**
     * Cast stored value
     */
    private function castValue(mixed $value, bool $isArray): mixed
    {
        if ($isArray) {
            return array_values(
                array_filter(
                    array_map('trim', explode(',', (string) $value)),
                    static fn ($v) => $v !== ''
                )
            );
        }

        return $value;
    }

    /**
     * Enable immutable mode
     */
    public function lock(): void
    {
        $this->immutable = true;
    }

    /**
     * Get value using dot notation
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureLoaded();

        $segments = explode('.', $key);
        $value = $this->settings;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Typed getters
     */
    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);
        return is_array($value) ? $value : [$value];
    }

    /**
     * Set runtime value (optionally persist)
     */
    public function set(string $key, mixed $value, bool $persist = false): void
    {
        if ($this->immutable) {
            throw new RuntimeException('Settings are locked (immutable mode enabled)');
        }

        $this->ensureLoaded();

        $this->settings[$key] = $value;

        if ($persist) {
            $this->repository->update($key, $value);
        }
    }

    /**
     * Check existence
     */
    public function exists(string $key): bool
    {
        $this->ensureLoaded();
        return array_key_exists($key, $this->settings);
    }

    /**
     * Match value
     */
    public function has(string $key, mixed $value): bool
    {
        $current = $this->get($key);

        return is_array($current)
            ? in_array($value, $current, true)
            : $current === $value;
    }

    /**
     * Reload from storage
     */
    public function reload(): void
    {
        $this->loaded = false;
        $this->load();
    }

    /**
     * Get all settings
     */
    public function all(): array
    {
        $this->ensureLoaded();
        return $this->settings;
    }
}
