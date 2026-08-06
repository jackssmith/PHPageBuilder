<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS\Block;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\ThemeBlock;

class BaseModel
{
    protected ThemeBlock $block;

    protected array $data = [];

    protected ?PageContract $page;

    protected bool $forPageBuilder = false;

    protected bool $doNotRender = false;

    protected bool $hasSkeleton = false;

    protected bool $hasDynamicSkeleton = false;

    public function __construct(
        ThemeBlock $block,
        array $data = [],
        ?PageContract $page = null,
        bool $forPageBuilder = false
    ) {
        $this->block = $block;
        $this->data = $data;
        $this->page = $page;
        $this->forPageBuilder = $forPageBuilder;

        if (phpb_in_editmode() && method_exists($this, 'initEdit')) {
            $this->initEdit();
        } else {
            $this->init();
        }
    }

    /**
     * Initialize model.
     */
    protected function init(): void
    {
    }

    /**
     * Get a setting.
     */
    public function setting(
        string $key,
        mixed $default = null,
        bool $allowHtml = false
    ): mixed {

        $value = $this->data['settings']['attributes'][$key]
            ?? $this->block->get("settings.{$key}.value")
            ?? $default;

        return $allowHtml ? $value : phpb_e((string) $value);
    }

    /**
     * Determine if a setting exists.
     */
    public function hasSetting(string $key): bool
    {
        return isset($this->data['settings']['attributes'][$key])
            || $this->block->get("settings.{$key}.value") !== null;
    }

    /**
     * Get arbitrary data using dot notation.
     */
    public function data(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->arrayGet($this->data, $key, $default);
    }

    /**
     * Check if data exists.
     */
    public function hasData(string $key): bool
    {
        return $this->arrayHas($this->data, $key);
    }

    /**
     * Get child block data.
     */
    public function childData(
        string $id,
        mixed $default = null
    ): mixed {
        return $this->data['blocks'][$id] ?? $default;
    }

    /**
     * Return all child blocks.
     */
    public function childBlocks(): array
    {
        return $this->data['blocks'] ?? [];
    }

    /**
     * Get page.
     */
    public function page(): ?PageContract
    {
        return $this->page;
    }

    /**
     * Get ThemeBlock.
     */
    public function block(): ThemeBlock
    {
        return $this->block;
    }

    /**
     * Return all data.
     */
    public function allData(): array
    {
        return $this->data;
    }

    /**
     * Whether rendered in builder.
     */
    public function isBuilder(): bool
    {
        return $this->forPageBuilder;
    }

    /**
     * Whether edit mode.
     */
    public function isEditMode(): bool
    {
        return phpb_in_editmode();
    }

    /**
     * Runtime render control.
     */
    public function disableRender(bool $state = true): static
    {
        $this->doNotRender = $state;

        return $this;
    }

    /**
     * Skeleton control.
     */
    public function skeleton(bool $state = true): static
    {
        $this->hasSkeleton = $state;

        return $this;
    }

    /**
     * Dynamic skeleton control.
     */
    public function dynamicSkeleton(bool $state = true): static
    {
        $this->hasDynamicSkeleton = $state;

        return $this;
    }

    public function doNotRender(): bool
    {
        return $this->doNotRender;
    }

    public function hasSkeleton(): bool
    {
        return $this->hasSkeleton;
    }

    public function hasDynamicSkeleton(): bool
    {
        return $this->hasDynamicSkeleton;
    }

    /**
     * Magic property getter.
     */
    public function __get(string $key): mixed
    {
        return $this->data($key);
    }

    /**
     * Magic property checker.
     */
    public function __isset(string $key): bool
    {
        return $this->hasData($key);
    }

    /**
     * Array helper with dot notation.
     */
    protected function arrayGet(
        array $array,
        string $key,
        mixed $default = null
    ): mixed {

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {

            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Dot notation exists helper.
     */
    protected function arrayHas(
        array $array,
        string $key
    ): bool {

        foreach (explode('.', $key) as $segment) {

            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Merge runtime data.
     */
    public function mergeData(array $data): static
    {
        $this->data = array_replace_recursive($this->data, $data);

        return $this;
    }

    /**
     * Export model state.
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'forPageBuilder' => $this->forPageBuilder,
            'doNotRender' => $this->doNotRender,
            'hasSkeleton' => $this->hasSkeleton,
            'hasDynamicSkeleton' => $this->hasDynamicSkeleton,
        ];
    }
}
