<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Contracts\LocaleInterface;

/**
 * Class TranslationProvider .
 * @package Vanilla\Site
 */
class TranslationProvider implements TranslationProviderInterface {
    /** @var LocaleInterface $locale */
    private $locale;

    /**
     * TranslationProvider constructor.
     * @param LocaleInterface $locale
     */
    public function __construct(LocaleInterface $locale) {
        $this->locale = $locale;
    }

    /**
     * @inheritdoc
     */
    public function supportsContentTranslation(): bool {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function translate(string $propertyKey, string $sourceValue): string {
        return $this->locale->translate($propertyKey, $sourceValue);
    }

    /**
     * @inheritdoc
     */
    public function translateContent(
        string $locale,
        string $resource,
        string $recordType = null,
        int $recordID = null,
        string $recordKey = null,
        string $propertyName,
        string $sourceValue = null
    ): string {
        // Placeholder (fallback to default value), since this providerdoes not support user content translation
        return $sourceValue;
    }

    /**
     * @inheritdoc
     */
    public function translateProperties(
        string $locale,
        string $resource,
        string $recordType,
        string $idFieldName,
        array $records,
        array $properties
    ): array {
        // Placeholder (fallback to default value), since this providerdoes not support user content translation
        return $records;
    }
}
