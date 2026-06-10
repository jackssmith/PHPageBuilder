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
        return (bool) $this->get($key, $default);
    }

    public function getArray(
        string $key,
        array $default = []
    ): array {
        $value = $this->get($key, $default);

        return is_array($value)
            ? $value
            : [$value];
    }

    public function set(
        string $key,
        mixed $value,
        bool $persist = false
    ): self {
        $this->assertMutable();
        $this->ensureLoaded();

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

        if ($persist) {
            $this->repository->update($key, $value);
        }

        return $this;
    }

    public function remove(
        string $key
    ): self {
        $this->assertMutable();
        $this->ensureLoaded();

        unset($this->settings[$key]);

        return $this;
    }

    public function merge(
        array $settings
    ): self {
        $this->assertMutable();
        $this->ensureLoaded();

        $this->settings = array_replace_recursive(
            $this->settings,
            $settings
        );

        return $this;
    }

    public function exists(
        string $key
    ): bool {
        return $this->get($key, '__missing__') !== '__missing__';
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

    public function clear(): self
    {
        $this->assertMutable();

        $this->settings = [];

        return $this;
    }

    public function all(): array
    {
        $this->ensureLoaded();

        return $this->settings;
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

    public function count(): int
    {
        $this->ensureLoaded();

        return count($this->settings);
    }

    public function getIterator(): Traversable
    {
        $this->ensureLoaded();

        return new ArrayIterator($this->settings);
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }

    public function offsetExists(
        mixed $offset
    ): bool {
        return $this->exists((string) $offset);
    }

    public function offsetGet(
        mixed $offset
    ): mixed {
        return $this->get((string) $offset);
    }

    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        $this->set((string) $offset, $value);
    }

    public function offsetUnset(
        mixed $offset
    ): void {
        $this->remove((string) $offset);
    }

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
                    static fn (string $item): string => trim($item),
                    explode(',', (string) $value)
                ),
                static fn (string $item): bool => $item !== ''
            )
        );
    }
}
