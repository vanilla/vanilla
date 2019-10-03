<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Search;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use ReflectionClass;

/**
 * Trait SearchRecordTypeTrait
 * @package Vanilla\Contracts\Search
 */
trait SearchRecordTypeTrait {
    /**
     * Returns the list of all const properties required for this trait
     *
     * @return array
     */
    public static function required(): array {
        return [
            'TYPE',
            'API_TYPE_KEY',
            'SPHINX_DTYPE',
            'SPHINX_INDEX',
            'GUID_OFFSET',
            'GUID_MULTIPLIER',
            'SUB_KEY',
            'CHECKBOX_LABEL',
            'PROVIDER_GROUP'
        ];
    }

    /**
     * SearchRecordTypeTrait constructor.
     */
    public function __construct() {
        $oClass = new ReflectionClass(__CLASS__);
        $props = $oClass->getConstants();
        foreach (self::required() as $const) {
            if (!isset($props, $const)) {
                throw new \Exception('SearchRecordTypeInterface require const '.$const);
            }
        }
    }

    /**
     * Check if current  search type equal to foreign object
     *
     * @param \Vanilla\Contracts\Search\SearchRecordTypeInterface $searchType
     * @return bool
     */
    public function equal(SearchRecordTypeInterface $searchType): bool {
        $self = new ReflectionClass(__CLASS__);
        $selfProps = $self->getConstants();
        $clone = new ReflectionClass(get_class($searchType));
        $cloneProps = $clone->getConstants();
        foreach (self::required() as $const) {
            if ($selfProps[$const] !== $cloneProps[$const]) {
                return false;
            }
        }
        return true;
    }

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
