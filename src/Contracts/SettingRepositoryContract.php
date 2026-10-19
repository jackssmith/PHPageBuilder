<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

/**
 * Defines the contract for managing website settings.
 */
interface SettingRepositoryContract
{
    /**
     * Replace all existing website settings with the provided data.
     *
     * Implementations should define how settings are persisted and
     * whether existing settings are removed before new settings are saved.
     *
     * @param array<string, mixed> $data Associative array of setting keys and values.
     *
     * @return bool True if the settings were updated successfully; otherwise, false.
     */
    public function updateSettings(array $data): bool;
}
