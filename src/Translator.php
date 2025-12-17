<?php

declare(strict_types=1);

namespace PHPageBuilder;

class Translator
{
    /**
     * Customize or override default translations.
     *
     * @param array<string, string> $translations
     * @return array<string, string>
     */
    public function customize(array $translations): array
    {
        // Example: override or add custom translations
        $customTranslations = [
            // 'welcome' => 'Welcome to PHPageBuilder',
        ];

        // Merge custom translations with existing ones
        return array_merge($translations, $customTranslations);
    }
}
