<?php

namespace MachineTranslation\Services;

/**
 * Key functionality required for the Community Machine Translation feature.
 */
interface CommunityMachineTranslationServiceInterface
{
    /**
     * Get number of locales from the config.
     *
     * @return int
     */
    public static function getLocaleCount(): int;

    /**
     * Get the locales selected for translation.
     *
     * @return array
     */
    public static function getLocaleSelected(): array;

    /**
     * Check if AI translations are enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool;

    /**
     * Translate the content into the selected locales.
     *
     * @param array $texts
     * @param string $originLanguage
     *
     * @return array
     */
    public function translate(array $texts, string $originLanguage): array;

    /**
     * Generate original language from a provided text.
     *
     * @param array $request
     * @return string
     */
    public function generateOriginalLanguage(array $request): string;
}
