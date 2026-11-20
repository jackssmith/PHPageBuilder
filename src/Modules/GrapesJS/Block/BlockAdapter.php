<?php

namespace PHPageBuilder\Modules\GrapesJS\Block;

use PHPageBuilder\Modules\GrapesJS\PageRenderer;
use PHPageBuilder\ThemeBlock;
use Exception;

/**
 * Class BlockAdapter
 *
 * Adapts ThemeBlock into objects understood by GrapesJS.
 */
class BlockAdapter
{
    protected $pageRenderer;
    protected $block;

    public function __construct(PageRenderer $pageRenderer, ThemeBlock $block)
    {
        $this->pageRenderer = $pageRenderer;
        $this->block = $block;
    }

    /**
     * Get block slug.
     */
    public function getSlug()
    {
        return $this->block->getSlug();
    }

    /**
     * Get block title.
     */
    public function getTitle()
    {
        return $this->block->get('title')
            ?: str_replace('-', ' ', ucfirst($this->getSlug()));
    }

    /**
     * Get block category.
     */
    public function getCategory()
    {
        return $this->block->get('category')
            ?: phpb_trans('pagebuilder.default-category');
    }

    /**
     * Get block description.
     */
    public function getDescription()
    {
        return $this->block->get('description') ?? '';
    }

    /**
     * Get block icon.
     */
    public function getIcon()
    {
        return $this->block->get('icon') ?: 'fa fa-bars';
    }

    /**
     * Get block thumbnail URL.
     */
    public function getThumbnail()
    {
        if ($this->hasThumbnail()) {
            return phpb_full_url($this->block->getThumbUrl());
        }

        return '';
    }

    /**
     * Check if block has thumbnail.
     */
    public function hasThumbnail()
    {
        return file_exists($this->block->getThumbPath());
    }

    /**
     * Check if block has custom icon.
     */
    public function hasCustomIcon()
    {
        return !empty($this->block->get('icon'));
    }

    /**
     * Check if block is HTML.
     */
    public function isHtmlBlock()
    {
        return $this->block->isHtmlBlock();
    }

    /**
     * Get dynamic block configuration.
     */
    public function getDynamicConfig()
    {
        return $this->block::getDynamicConfig($this->getSlug()) ?? [];
    }

    /**
     * Get all block settings.
     */
    public function getSettings()
    {
        $config = $this->getDynamicConfig();

        return $config['settings']
            ?? $this->block->get('settings')
            ?? [];
    }

    /**
     * Get a specific setting.
     */
    public function getSetting($name, $default = null)
    {
        $settings = $this->getSettings();

        return $settings[$name] ?? $default;
    }

    /**
     * Get default settings values.
     */
    public function getDefaultSettings()
    {
        $defaults = [];

        foreach ($this->getSettings() as $name => $setting) {
            $defaults[$name] = $setting['value'] ?? '';
        }

        return $defaults;
    }

    /**
     * Merge default settings with provided values.
     */
    public function getSettingsWithValues(array $values = [])
    {
        return array_replace($this->getDefaultSettings(), $values);
    }

    /**
     * Check if block has settings.
     */
    public function hasSettings()
    {
        return !empty($this->getSettings());
    }

    /**
     * Validate block settings.
     */
    public function validateSettings(array $values)
    {
        $errors = [];
        $settings = $this->getSettings();

        foreach ($values as $name => $value) {
            if (!isset($settings[$name])) {
                $errors[$name] = 'Unknown setting.';
                continue;
            }

            $setting = $settings[$name];
            $type = $setting['type'] ?? 'text';

            if (
                isset($setting['required'])
                && $setting['required']
                && ($value === '' || $value === null)
            ) {
                $errors[$name] = 'This field is required.';
                continue;
            }

            if ($type === 'number' && !is_numeric($value)) {
                $errors[$name] = 'This field must be numeric.';
            }

            if ($type === 'yes_no' && !in_array($value, [0, 1, '0', '1'], true)) {
                $errors[$name] = 'Invalid yes/no value.';
            }

            if ($type === 'select' && isset($setting['options'])) {
                $allowed = array_column($setting['options'], 'id');

                if (!in_array($value, $allowed, false)) {
                    $errors[$name] = 'Invalid select option.';
                }
            }
        }

        return $errors;
    }

    /**
     * Get block attributes.
     */
    public function getBlockAttributes()
    {
        return [
            'class' => $this->getIcon(),
            'data-block' => $this->getSlug(),
        ];
    }

    /**
     * Get GrapesJS traits.
     */
    public function getBlockTraitsArray()
    {
        $traits = [];

        foreach ($this->getSettings() as $name => $setting) {
            if (!isset($setting['label'])) {
                continue;
            }

            $traits[] = [
                'type' => $setting['type'] ?? 'text',
                'name' => $name,
                'label' => $setting['label'],
                'default' => $setting['value'] ?? '',
            ];
        }

        return $traits;
    }

    /**
     * Get custom CSS.
     */
    public function getCustomCss()
    {
        return $this->block->get('css') ?? '';
    }

    /**
     * Get custom JavaScript.
     */
    public function getCustomJavascript()
    {
        return $this->block->get('javascript') ?? '';
    }

    /**
     * Return GrapesJS block manager configuration.
     *
     * @throws Exception
     */
    public function getBlockManagerArray()
    {
        $content = $this->pageRenderer->renderBlock($this->getSlug());

        $img = '';

        if ($this->hasThumbnail()) {
            $img = '<div class="block-thumb" style="background-image: url('
                . htmlspecialchars($this->getThumbnail(), ENT_QUOTES, 'UTF-8')
                . '); background-size: cover"></div>';
        }

        $data = [
            'label' => $img . htmlspecialchars($this->getTitle(), ENT_QUOTES, 'UTF-8'),
            'category' => $this->getCategory(),
            'content' => $content,
        ];

        if (!$this->hasThumbnail()) {
            $data['attributes'] = $this->getBlockAttributes();
        }

        return $data;
    }

    /**
     * Return GrapesJS block settings.
     */
    public function getBlockSettingsArray()
    {
        if ($this->isHtmlBlock()) {
            return [];
        }

        $blockSettings = $this->getSettings();

        if (!is_array($blockSettings)) {
            return [];
        }

        $settings = [];

        foreach ($blockSettings as $name => $blockSetting) {
            if (!isset($blockSetting['label'])) {
                continue;
            }

            $type = $blockSetting['type'] ?? 'text';

            $setting = [
                'type' => $type,
                'name' => $name,
                'label' => $blockSetting['label'],
                'default-value' => $blockSetting['value'] ?? '',
                'placeholder' => $blockSetting['placeholder'] ?? '',
            ];

            if ($type === 'select') {
                $setting['options'] = $blockSetting['options'] ?? [];
            } elseif ($type === 'yes_no') {
                $setting['type'] = 'select';

                $setting['options'] = [
                    [
                        'id' => 0,
                        'name' => phpb_trans('pagebuilder.no'),
                    ],
                    [
                        'id' => 1,
                        'name' => phpb_trans('pagebuilder.yes'),
                    ],
                ];
            }

            $settings[] = $setting;
        }

        return $settings;
    }

    /**
     * Export block configuration as JSON.
     */
    public function toJson($options = JSON_PRETTY_PRINT)
    {
        return json_encode([
            'slug' => $this->getSlug(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'category' => $this->getCategory(),
            'icon' => $this->getIcon(),
            'thumbnail' => $this->getThumbnail(),
            'settings' => $this->getSettings(),
            'traits' => $this->getBlockTraitsArray(),
            'css' => $this->getCustomCss(),
            'javascript' => $this->getCustomJavascript(),
        ], $options);
    }
}
