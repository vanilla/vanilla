<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models\SearchRecords;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;

/**
 * Class SearchRecordTypeProvider
 * @package Vanilla\AdvancedSearch\Models
 */
class SearchRecordTypeProvider implements SearchRecordTypeProviderInterface {
    /** @var $searchRecordTypes SearchRecordTypeInterface[] */
    private $types = [];

    /** @var $providerGroups string[] */
    private $providerGroups = [];

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        $res = [];
        foreach ($this->types as $recordType) {
            if (in_array($recordType->getProviderGroup(), $this->providerGroups)) {
                $res[] = $recordType;
            }
        }
        return $res;
    }

    /**
     * @inheritdoc
     */
    public function setType(SearchRecordTypeInterface $recordType) {
        $this->types[] = $recordType;
    }

    /**
     * @inheritdoc
     */
    public function getType(string $typeKey): ?SearchRecordTypeInterface {
        $result = null;
        foreach ($this->types as $type) {
            if ($type->getKey() === $typeKey) {
                $result = $type;
                break;
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getByDType(int $dtype): ?SearchRecordTypeInterface {
        $result = null;
        foreach ($this->types as $recordType) {
            if (in_array($recordType->getProviderGroup(), $this->providerGroups)) {
                if ($dtype === $recordType->getDType()) {
                    $result = $recordType;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function addProviderGroup(string $providerGroup) {
        $this->providerGroups[] = $providerGroup;
    }
}
