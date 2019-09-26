<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Search;

/**
 * Trait SearchRecordTypeTrait
 * @package Vanilla\Contracts\Search
 */
trait SearchRecordTypeTrait {
    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return self::TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getApiTypeKey(): string {
        return self::API_TYPE_KEY;
    }

    /**
     * @inheritdoc
     */
    public function getDType(): int {
        return self::SPHINX_DTYPE;
    }

    /**
     * @inheritdoc
     */
    public function getIndexName(): string {
        return self::SPHINX_INDEX;
    }

    /**
     * @inheritdoc
     */
    public function getRecordID(int $guid): ?int {
        return ($guid - self::GUID_OFFSET) / self::GUID_MULTIPLIER;
    }

    /**
     * @inheritdoc
     */
    public function getDocuments(array $IDs, \SearchModel $searchModel): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getCheckBoxId(): string {
        return self::TYPE.'_'.self::SUB_KEY;
    }

    /**
     * @inheritdoc
     */
    public function getCheckBoxLabel(): string {
        return self::CHECKBOX_LABEL;
    }

    /**
     * @inheritdoc
     */
    public function getModel() {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getProviderGroup(): string {
        return self::PROVIDER_GROUP;
    }
}
