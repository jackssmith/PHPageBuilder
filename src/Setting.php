<?php

declare(strict_types=1);

namespace PHPageBuilder;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonException;
use JsonSerializable;
use PHPageBuilder\Contracts\SettingContract;
use PHPageBuilder\Repositories\SettingRepository;
use RuntimeException;
use Traversable;
use InvalidArgumentException;
use LogicException;

final class Setting implements
    SettingContract,
    ArrayAccess,
    IteratorAggregate,
    Countable,
    JsonSerializable
{
    private array $settings = [];

    private bool $loaded = false;

    private bool $locked = false;

    public function __construct(
        private readonly SettingRepository $repository
    ) {
    }

    public static function create(
        SettingRepository $repository
    ): self {
        return (new self($repository))->load();
    }

    /* =========================================
     * Loading
     * ========================================= */

    public function load(): self
    {
        $settings = [];

        foreach ($this->repository->getAll() as $row) {
            $settings[$row['setting']] = $this->transformValue(
                $row['value'],
                (bool) ($row['is_array'] ?? false)
            );
        }

        $this->settings = $settings;
        $this->loaded = true;

        return $this;
    }

    public function reload(): self
    {
        $this->loaded = false;

        return $this->load();
    }

    public function reset(): self
    {
        return $this->reload();
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /* =========================================
     * Locking
     * ========================================= */

    public function lock(): self
    {
        $this->locked = true;

        return $this;
    }

    public function unlock(): self
    {
        $this->locked = false;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    /* =========================================
     * Getting values
     * ========================================= */

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $this->ensureLoaded();

        if ($key === '') {
            return $this->settings;
        }

        $value = $this->settings;

        foreach (explode('.', $key) as $segment) {
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

    public function getOrFail(string $key): mixed
    {
        if (!$this->exists($key)) {
            throw new InvalidArgumentException(
                "Setting [{$key}] does not exist."
            );
        }

        return $this->get($key);
    }

    public function getString(
        string $key,
        string $default = ''
    ): string {
        return (string) $this->get($key, $default);
    }

    public function getInt(
        string $key,
        int $default = 0
    ): int {
        return (int) $this->get($key, $default);
    }

    public function getFloat(
        string $key,
        float $default = 0.0
    ): float {
        return (float) $this->get($key, $default);
    }

    public function getBool(
        string $key,
        bool $default = false
    ): bool {
        $value = $this->get($key, $default);

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? $default;
    }

    public function getArray(
        string $key,
        array $default = []
    ): array {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : [$value];
    }

    public function getObject(
        string $key,
        mixed $default = null
    ): object {
        $value = $this->get($key, $default);

        return (object) $value;
    }

    public function getFirst(
        array $keys,
        mixed $default = null
    ): mixed {
        foreach ($keys as $key) {
            if ($this->exists($key)) {
                return $this->get($key);
            }
        }

        return $default;
    }

    /* =========================================
     * Setting values
     * ========================================= */

    public function set(
        string $key,
        mixed $value,
        bool $persist = false
    ): self {
        $this->assertMutable();
        $this->ensureLoaded();

        $this->validateKey($key);

        $this->writeValue($key, $value);

        if ($persist) {
            $this->repository->update($key, $value);
        }

        return $this;
    }

    public function setMany(
        array $settings,
        bool $persist = false
    ): self {
        foreach ($settings as $key => $value) {
            $this->set((string) $key, $value, $persist);
        }

        return $this;
    }

    public function setDefault(
        string $key,
        mixed $value
    ): self {
        if (!$this->exists($key)) {
            $this->set($key, $value);
        }

        return $this;
    }

    public function replace(array $settings): self
    {
        $this->assertMutable();
        $this->ensureLoaded();

        $this->settings = $settings;

        return $this;
    }

    public function merge(array $settings): self
    {
        $this->assertMutable();
        $this->ensureLoaded();

        $this->settings = array_replace_recursive(
            $this->settings,
            $settings
        );

        return $this;
    }

    public function mergeRecursive(array $settings): self
    {
        return $this->merge($settings);
    }

    /* =========================================
     * Array operations
     * ========================================= */

    public function push(
        string $key,
        mixed $value
    ): self {
        $this->assertMutable();

        $items = $this->getArray($key);

        $items[] = $value;

        return $this->set($key, $items);
    }

    public function prepend(
        string $key,
        mixed $value
    ): self {
        $this->assertMutable();

        $items = $this->getArray($key);

        array_unshift($items, $value);

        return $this->set($key, $items);
    }

    public function pull(
        string $key,
        mixed $value
    ): self {
        $this->assertMutable();

        $items = $this->getArray($key);

        $items = array_values(
            array_filter(
                $items,
                static fn ($item): bool => $item !== $value
            )
        );

        return $this->set($key, $items);
    }

    public function pop(string $key): mixed
    {
        $this->assertMutable();

        $items = $this->getArray($key);

        $value = array_pop($items);

        $this->set($key, $items);

        return $value;
    }

    public function shift(string $key): mixed
    {
        $this->assertMutable();

        $items = $this->getArray($key);

        $value = array_shift($items);

        $this->set($key, $items);

        return $value;
    }

    public function unique(string $key): self
    {
        $this->assertMutable();

        return $this->set(
            $key,
            array_values(array_unique($this->getArray($key)))
        );
    }

    /* =========================================
     * Numeric operations
     * ========================================= */

    public function increment(
        string $key,
        int|float $amount = 1
    ): self {
        $value = $this->get($key, 0);

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                "Setting [{$key}] is not numeric."
            );
        }

        return $this->set($key, $value + $amount);
    }

    public function decrement(
        string $key,
        int|float $amount = 1
    ): self {
        return $this->increment($key, -$amount);
    }

    /* =========================================
     * Removing values
     * ========================================= */

    public function remove(string $key): self
    {
        $this->assertMutable();
        $this->ensureLoaded();

        $segments = explode('.', $key);
        $last = array_pop($segments);

        $current = &$this->settings;

        foreach ($segments as $segment) {
            if (
                !isset($current[$segment]) ||
                !is_array($current[$segment])
            ) {
                return $this;
            }

            $current = &$current[$segment];
        }

        unset($current[$last]);

        return $this;
    }

    public function forget(string ...$keys): self
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }

        return $this;
    }

    public function clear(): self
    {
        $this->assertMutable();

        $this->settings = [];

        return $this;
    }

    /* =========================================
     * Checking values
     * ========================================= */

    public function exists(string $key): bool
    {
        $this->ensureLoaded();

        $value = $this->settings;

        if ($key === '') {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (
                !is_array($value) ||
                !array_key_exists($segment, $value)
            ) {
                return false;
            }

            $value = $value[$segment];
        }

        return true;
    }

    public function has(
        string $key,
        mixed $value
    ): bool {
        $current = $this->get($key);

        return is_array($current)
            ? in_array($value, $current, true)
            : $current === $value;
    }

    public function isEmpty(): bool
    {
        return $this->all() === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /* =========================================
     * Filtering and extracting
     * ========================================= */

    public function only(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if ($this->exists($key)) {
                $result[$key] = $this->get($key);
            }
        }

        return $result;
    }

    public function except(array $keys): array
    {
        $result = $this->all();

        foreach ($keys as $key) {
            unset($result[$key]);
        }

        return $result;
    }

    public function filter(
        callable $callback
    ): array {
        return array_filter(
            $this->all(),
            $callback,
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function map(
        callable $callback
    ): array {
        return array_map(
            $callback,
            $this->all(),
            array_keys($this->all())
        );
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function values(): array
    {
        return array_values($this->all());
    }

    /* =========================================
     * Exporting
     * ========================================= */

    public function all(): array
    {
        $this->ensureLoaded();

        return $this->settings;
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public function toJson(
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ): string {
        try {
            return json_encode(
                $this->all(),
                JSON_THROW_ON_ERROR | $flags
            );
        } catch (JsonException) {
            return '{}';
        }
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }

    /* =========================================
     * Iteration
     * ========================================= */

    public function count(): int
    {
        return count($this->all());
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->exists((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        if ($offset === null) {
            throw new InvalidArgumentException(
                'Settings require a named key.'
            );
        }

        $this->set((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove((string) $offset);
    }

    /* =========================================
     * Internal methods
     * ========================================= */

    private function ensureLoaded(): void
    {
        if (!$this->loaded) {
            $this->load();
        }
    }

    private function assertMutable(): void
    {
        if ($this->locked) {
            throw new RuntimeException(
                'Settings are locked and cannot be modified.'
            );
        }
    }

    private function validateKey(string $key): void
    {
        if (
            $key === '' ||
            str_starts_with($key, '.') ||
            str_ends_with($key, '.') ||
            str_contains($key, '..')
        ) {
            throw new InvalidArgumentException(
                'Invalid setting key.'
            );
        }
    }

    private function writeValue(
        string $key,
        mixed $value
    ): void {
        $segments = explode('.', $key);
        $current = &$this->settings;

        while (count($segments) > 1) {
            $segment = array_shift($segments);

            if (
                !isset($current[$segment]) ||
                !is_array($current[$segment])
            ) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current[array_shift($segments)] = $value;

        unset($current);
    }

    private function transformValue(
        mixed $value,
        bool $isArray
    ): mixed {
        if (!$isArray) {
            return $value;
        }

        return array_values(
            array_filter(
                array_map(
                    static fn ($item): string => trim((string) $item),
                    explode(',', (string) $value)
                ),
                static fn (string $item): bool => $item !== ''
            )
        );
    }
}
