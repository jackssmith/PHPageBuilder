<?php

declare(strict_types=1);

namespace PHPageBuilder;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use PHPageBuilder\Contracts\SettingContract;
use PHPageBuilder\Repositories\SettingRepository;
use RuntimeException;
use Traversable;

final class Setting implements
    SettingContract,
    ArrayAccess,
    IteratorAggregate,
    Countable,
    JsonSerializable
{
    /**
     * Loaded settings container
     */
    private array $settings = [];

    /**
     * Settings loaded state
     */
    private bool $loaded = false;

    /**
     * Immutable state
     */
    private bool $locked = false;

    public function __construct(
        private readonly SettingRepository $repository
    ) {}

    /**
     * Static factory
     */
    public static function create(
        SettingRepository $repository
    ): self {
        $instance = new self($repository);

        $instance->load();

        return $instance;
    }

    /**
     * Load all settings
     */
    public function load(): self
    {
        $this->settings = [];

        foreach ($this->repository->getAll() as $row) {
            $this->settings[$row['setting']] = $this->parseValue(
                $row['value'],
                (bool) $row['is_array']
            );
        }

        $this->loaded = true;

        return $this;
    }

    /**
     * Reload settings
     */
    public function reload(): self
    {
        $this->loaded = false;

        return $this->load();
    }

    /**
     * Ensure data is loaded
     */
    private function ensureLoaded(): void
    {
        if (!$this->loaded) {
            $this->load();
        }
    }

    /**
     * Parse repository value
     */
    private function parseValue(
        mixed $value,
        bool $isArray
    ): mixed {
        if (!$isArray) {
            return $value;
        }

        return $this->normalizeArray(
            explode(',', (string) $value)
        );
    }

    /**
     * Normalize array items
     */
    private function normalizeArray(
        array $items
    ): array {
        return array_values(
            array_filter(
                array_map(
                    static fn ($item): string => trim((string) $item),
                    $items
                ),
                static fn ($item): bool => $item !== ''
            )
        );
    }

    /**
     * Lock settings
     */
    public function lock(): self
    {
        $this->locked = true;

        return $this;
    }

    /**
     * Unlock settings
     */
    public function unlock(): self
    {
        $this->locked = false;

        return $this;
    }

    /**
     * Check lock state
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Assert mutability
     */
    private function assertMutable(): void
    {
        if ($this->locked) {
            throw new RuntimeException(
                'Settings are locked.'
            );
        }
    }

    /**
     * Get setting using dot notation
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $this->ensureLoaded();

        $segments = explode('.', $key);

        $value = $this->settings;

        foreach ($segments as $segment) {
            if (
                !is_array($value) ||
                !array_key_exists($segment, $value)
            ) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get string value
     */
    public function getString(
        string $key,
        string $default = ''
    ): string {
        return (string) $this->get($key, $default);
    }

    /**
     * Get integer value
     */
    public function getInt(
        string $key,
        int $default = 0
    ): int {
        return (int) $this->get($key, $default);
    }

    /**
     * Get float value
     */
    public function getFloat(
        string $key,
        float $default = 0.0
    ): float {
        return (float) $this->get($key, $default);
    }

    /**
     * Get boolean value
     */
    public function getBool(
        string $key,
        bool $default = false
    ): bool {
        return (bool) $this->get($key, $default);
    }

    /**
     * Get array value
     */
    public function getArray(
        string $key,
        array $default = []
    ): array {
        $value = $this->get($key, $default);

        return is_array($value)
            ? $value
            : [$value];
    }

    /**
     * Set setting value
     */
    public function set(
        string $key,
        mixed $value,
        bool $persist = false
    ): self {
        $this->assertMutable();

        $this->ensureLoaded();

        $this->settings[$key] = $value;

        if ($persist) {
            $this->repository->update(
                $key,
                $value
            );
        }

        return $this;
    }

    /**
     * Remove setting
     */
    public function remove(
        string $key
    ): self {
        $this->assertMutable();

        unset($this->settings[$key]);

        return $this;
    }

    /**
     * Merge settings
     */
    public function merge(
        array $settings
    ): self {
        $this->assertMutable();

        $this->settings = array_replace_recursive(
            $this->settings,
            $settings
        );

        return $this;
    }

    /**
     * Determine whether key exists
     */
    public function exists(
        string $key
    ): bool {
        $this->ensureLoaded();

        return array_key_exists(
            $key,
            $this->settings
        );
    }

    /**
     * Determine whether setting matches value
     */
    public function has(
        string $key,
        mixed $value
    ): bool {
        $current = $this->get($key);

        return is_array($current)
            ? in_array($value, $current, true)
            : $current === $value;
    }

    /**
     * Clear settings
     */
    public function clear(): self
    {
        $this->assertMutable();

        $this->settings = [];

        return $this;
    }

    /**
     * Export all settings
     */
    public function all(): array
    {
        $this->ensureLoaded();

        return $this->settings;
    }

    /**
     * Export JSON
     */
    public function toJson(
        int $flags = JSON_PRETTY_PRINT
    ): string {
        return json_encode(
            $this->settings,
            $flags
        ) ?: '{}';
    }

    /**
     * Count settings
     */
    public function count(): int
    {
        return count($this->settings);
    }

    /**
     * IteratorAggregate implementation
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator(
            $this->settings
        );
    }

    /**
     * JsonSerializable implementation
     */
    public function jsonSerialize(): array
    {
        return $this->settings;
    }

    /**
     * ArrayAccess exists
     */
    public function offsetExists(
        mixed $offset
    ): bool {
        return $this->exists(
            (string) $offset
        );
    }

    /**
     * ArrayAccess get
     */
    public function offsetGet(
        mixed $offset
    ): mixed {
        return $this->get(
            (string) $offset
        );
    }

    /**
     * ArrayAccess set
     */
    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        $this->set(
            (string) $offset,
            $value
        );
    }

    /**
     * ArrayAccess unset
     */
    public function offsetUnset(
        mixed $offset
    ): void {
        $this->remove(
            (string) $offset
        );
    }
}
