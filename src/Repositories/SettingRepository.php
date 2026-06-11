<?php

declare(strict_types=1);
namespace PHPageBuilder\Repositories;
use Illuminate\Database\DatabaseManager;
use JsonException;
use PHPageBuilder\Contracts\SettingRepositoryContract;
use RuntimeException;

final class SettingRepository extends BaseRepository implements SettingRepositoryContract
{
    private const TABLE = 'settings';

    public function __construct(
        private readonly DatabaseManager $database,
    ) {
        $this->table = self::TABLE;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function replaceAll(array $settings): bool
    {
        if ($settings === []) {
            return true;
        }

        return $this->database->transaction(
            fn (): bool => $this->replaceSettings($settings)
        );
    }

    public function set(string $key, mixed $value): bool
    {
        return $this->updateOrCreate(
            ['setting' => $key],
            $this->createPayload($key, $value),
        ) !== null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->findBy('setting', $key);

        if ($setting === null) {
            return $default;
        }

        return $this->fromStorage(
            (string) $setting->value,
            (bool) $setting->is_array,
        );
    }

    public function delete(string $key): bool
    {
        return $this->query()
            ->where('setting', $key)
            ->delete() > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $settings = [];

        foreach ($this->query()->get() as $row) {
            $settings[$row->setting] = $this->fromStorage(
                (string) $row->value,
                (bool) $row->is_array,
            );
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function replaceSettings(array $settings): bool
    {
        $this->destroyAll();

        $payloads = [];

        foreach ($settings as $key => $value) {
            $payloads[] = $this->createPayload($key, $value);
        }

        return $this->query()->insert($payloads);
    }

    /**
     * @return array{
     *     setting:string,
     *     value:string,
     *     is_array:bool
     * }
     */
    private function createPayload(string $key, mixed $value): array
    {
        return [
            'setting' => $key,
            'value' => $this->toStorage($value, $key),
            'is_array' => is_array($value),
        ];
    }

    private function toStorage(mixed $value, string $key): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $e) {
            throw new RuntimeException(
                sprintf('Failed to encode setting "%s".', $key),
                previous: $e,
            );
        }
    }

    private function fromStorage(string $value, bool $isArray): mixed
    {
        if (! $isArray) {
            return $value;
        }

        try {
            return json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new RuntimeException(
                'Failed to decode stored setting.',
                previous: $e,
            );
        }
    }
}
