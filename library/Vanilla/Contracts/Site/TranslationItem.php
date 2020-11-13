<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2010-2018 Vanilla Forums Inc
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Class TranslationItem
 */
class TranslationItem {
    /** @var string $resource */
    private $resource;

    /** @var int $recordID */
    private $recordID;

    /** @var string $locale */
    private $locale;

    /** @var string $property */
    private $property;

    /** @var string $translation */
    private $translation;

    /**
     * TranslationItem constructor.
     *
     * @param string $combinedKey
     * @param string $locale
     * @param string $translation
     */
    public function __construct(
        string $combinedKey,
        string $locale,
        string $translation
    ) {
        // resource key pattern: {model}.{id}.{propertyKey}
        $info = explode('.', $combinedKey);

        $this->resource = $info[0];
        $this->recordID = (int)$info[1];
        $this->locale = $locale;
        $this->property = $info[2];
        $this->translation = $translation;
    }

    /**
     * @return string
     */
    public function getResource(): string {
        return $this->resource;
    }

    /**
     * @return int
     */
    public function getRecordID(): int {
        return $this->recordID;
    }

    /**
     * @return string
     */
    public function getLocale(): string {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getProperty(): string {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getTranslation(): string {
        return $this->translation;
    }
}
