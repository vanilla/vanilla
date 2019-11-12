<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\TranslationProviderInterface;

/**
 * Class TranslationProvider
 * @package Vanilla\Site
 */
class TranslationProvider implements TranslationProviderInterface {
    /**
     * @inheritdoc
     */
    public function supportContentTranslation(): bool {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function translate(string $propertyKey, $sourceValue): string {
        return Gdn::translate($propertyKey, $sourceValue);
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
}
