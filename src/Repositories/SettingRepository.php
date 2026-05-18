<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use JsonException;
use PHPageBuilder\Contracts\SettingRepositoryContract;
use RuntimeException;

final class SettingRepository extends BaseRepository implements SettingRepositoryContract
{
    private const TABLE = 'settings';

    /**
     * SettingRepository constructor.
     */
    public function __construct(
        private readonly DatabaseManager $database
    ) {
        $this->table = self::TABLE;
    }

    /**
     * Replace all application settings with the provided dataset.
     *
     * @param array<string, mixed> $settings
     */
    public function replaceAll(array $settings): bool
    {
        if ($settings === []) {
            return true;
        }

        return $this->database->transaction(
            fn (): bool => $this->persistSettings($settings)
        );
    }

    /**
     * Store or update a single setting.
     */
    public function set(string $key, mixed $value): bool
    {
        return $this->updateOrCreate(
            ['setting' => $key],
            $this->buildPayload($key, $value)
        ) !== null;
    }

    /**
     * Retrieve a setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->findBy('setting', $key);

        if ($setting === null) {
            return $default;
        }

        return $this->decodeValue(
            $setting->value,
            (bool) $setting->is_array
        );
    }

    /**
     * Delete a setting by key.
     */
    public function delete(string $key): bool
    {
        return (bool) $this->query()
            ->where('setting', $key)
            ->delete();
    }

    /**
     * Return all settings as key => value pairs.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->query()
            ->get()
            ->mapWithKeys(function (object $setting): array {
                return [
                    $setting->setting => $this->decodeValue(
                        $setting->value,
                        (bool) $setting->is_array
                    ),
                ];
            })
            ->toArray();
    }

    /**
     * Persist all settings in bulk.
     *
     * @param array<string, mixed> $settings
     */
    private function persistSettings(array $settings): bool
    {
        $this->destroyAll();

        $payloads = Collection::make($settings)
            ->map(fn (mixed $value, string $key): array => $this->buildPayload($key, $value))
            ->values()
            ->all();

        return $this->query()->insert($payloads);
    }

    /**
     * Build a normalized database payload.
     *
     * @return array{
     *     setting: string,
     *     value: string,
     *     is_array: bool
     * }
     */
    private function buildPayload(string $key, mixed $value): array
    {
        return [
            'setting'  => $key,
            'value'    => $this->encodeValue($value),
            'is_array' => is_array($value),
        ];
    }

    /**
     * Encode a setting value for storage.
     */
    private function encodeValue(mixed $value): string
    {
        if (is_array($value)) {
            try {
                return json_encode(
                    $value,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                );
            } catch (JsonException $exception) {
                throw new RuntimeException(
                    'Failed to encode setting array.',
                    previous: $exception
                );
            }
        }

        return (string) $value;
    }

    /**
     * Decode a stored setting value.
     */
    private function decodeValue(string $value, bool $isArray): mixed
    {
        if (! $isArray) {
            return $value;
        }

        try {
            return json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Failed to decode setting array.',
                previous: $exception
            );
        }
    }
}
