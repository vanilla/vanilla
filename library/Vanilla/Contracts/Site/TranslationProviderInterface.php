<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Interface TranslationProviderInterface
 * @package Vanilla\Contracts\Site
 */
interface TranslationProviderInterface {
    /**
     * Return true when translation model supports user content translation
     *
     * @return bool
     */
    public function supportContentTranslation(): bool;

    /**
     * Translate some key to the current locale
     *
     * @param string $propertyKey
     * @param string $sourceValue
     * @return string
     */
    public function translate(string $propertyKey, string $sourceValue): string;

    /**
     * Translate content by recordType and recordID or recordKey
     *
     * @param string $locale
     * @param string $resource
     * @param string $recordType
     * @param int $recordID
     * @param string $recordKey
     * @param string $propertyName
     * @param string $sourceValue
     * @return string
     */
    public function translateContent(
        string $locale,
        string $resource,
        string $recordType,
        int $recordID,
        string $recordKey,
        string $propertyName,
        string $sourceValue
    ): string;
}
