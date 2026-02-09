<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use PHPageBuilder\Contracts\SettingRepositoryContract;
use Illuminate\Support\Facades\DB;

class SettingRepository extends BaseRepository implements SettingRepositoryContract
{
    /**
     * The settings database table.
     *
     * @var string
     */
    protected $table = 'settings';

    /**
     * Replace all website settings with the given data.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateSettings(array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        return DB::transaction(function () use ($data) {
            $this->destroyAll();

            foreach ($data as $key => $value) {
                $isArray = is_array($value);

                $this->create([
                    'setting'  => (string) $key,
                    'value'    => $isArray ? json_encode($value) : (string) $value,
                    'is_array' => $isArray,
                ]);
            }

            return true;
        });
    }
}
