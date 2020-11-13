<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Exception;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Traversable;
use Webmozart\Assert\Assert;

/**
 * Class for working with a collection of search types.
 */
final class SearchTypeCollection implements \IteratorAggregate {

    /** @var AbstractSearchType[] */
    private $allTypes;

    /**
     * DI.
     *
     * @param AbstractSearchType[] $allTypes
     */
    public function __construct(array $allTypes) {
        $this->allTypes = $allTypes;
    }

    /**
     * @return AbstractSearchType[]
     */
    public function getAllTypes(): array {
        return $this->allTypes;
    }

    /**
     * @return \Iterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->getAllTypes());
    }

    /**
     * Add an extra type to the collection.
     *
     * @param AbstractSearchType $type
     */
    public function addType(AbstractSearchType $type) {
        $foundTypes = $this->getByType($type->getType());
        if (empty($foundTypes)) {
            $this->allTypes[] = $type;
        }
    }

    /**
     * Get types based on some query parameters.
     *
     * @param array $params
     * - types: string[]
     * - recordTypes: string[]
     *
     * @return SearchTypeCollection
     */
    public function getFilteredCollection(array $params): SearchTypeCollection {
        $queryTypes = $params['types'] ?? [];
        $queryRecordTypes = $params['recordTypes'] ?? [];

        // Types are more specific than recordTypes, so when specified we should use the types.
        $shouldApplyRecordTypes = empty($queryTypes);
        Assert::isArray($queryTypes);
        Assert::isArray($queryRecordTypes);

        if (empty($queryTypes) && empty($queryRecordTypes)) {
            // No filters were passed so give back all types available together to the user.
            return new SearchTypeCollection($this->getDefaultTypes());
        }

        $validation = new Validation();
        $filteredTypes = [];

        foreach ($queryTypes as $type) {
            $foundTypes = $this->getByType($type);
            foreach ($foundTypes as $foundType) {
                if (!$foundType->userHasPermission()) {
                    $validation->addError(
                        'types',
                        sprintf("You don't have permission to search the %s type", $foundType->getType()),
                        ['status' => 403]
                    );
                    continue;
                }

                if (!in_array($foundType, $filteredTypes)) {
                    $filteredTypes[] = $foundType;
                }
            }
        }

        $extraRecordTypes = [];
        foreach ($queryRecordTypes as $recordType) {
            $foundTypes = $this->getByRecordType($recordType);
            foreach ($foundTypes as $foundType) {
                if (!$foundType->userHasPermission()) {
                    $validation->addError(
                        'recordTypes',
                        sprintf("You don't have permission to search the %s recordType", $foundType->getSearchGroup()),
                        ['status' => 403]
                    );
                    continue;
                }

                if (!in_array($foundType, $filteredTypes)) {
                    if ($shouldApplyRecordTypes) {
                        $filteredTypes[] = $foundType;
                    } else {
                        $extraRecordTypes[] = $foundType;
                    }
                }
            }
        }

        // Ensure exclusive types are exclusive.
        $mergedTypes = array_merge($extraRecordTypes, $filteredTypes);
        foreach ($mergedTypes as $searchType) {
            if ($searchType->isExclusiveType() && count($mergedTypes) > 1) {
                $validation->addError('types', sprintf("%s type cannot be searched with other types", $searchType->getType()));
            }
        }

        if (!$validation->isValid()) {
            throw new ValidationException($validation);
        }

        return new SearchTypeCollection($filteredTypes);
    }

    /**
     * Determine if the collection has an exclusive type.
     *
     * @return bool
     */
    public function hasExclusiveType(): bool {
        foreach ($this->allTypes as $searchType) {
            if ($searchType->isExclusiveType()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all of the search types that are applied by default.
     *
     * @return AbstractSearchType[]
     */
    public function getDefaultTypes(): array {
        $result = [];
        // Apply all non-exclusive types.
        foreach ($this->allTypes as $searchType) {
            if (!$searchType->isExclusiveType() && $searchType->userHasPermission()) {
                $result[] = $searchType;
            }
        }
        return $result;
    }

    /**
     * Get by type.
     *
     * @param string $type
     *
     * @return AbstractSearchType[]
     */
    public function getByType(string $type): array {
        $result = [];
        foreach ($this->allTypes as $searchType) {
            if ($type === $searchType->getType()) {
                $result[] = $searchType;
            }
        }
        return $result;
    }

    /**
     * Get by type.
     *
     * @param string $recordType
     *
     * @return AbstractSearchType[]
     */
    public function getByRecordType(string $recordType): array {
        $result = [];
        foreach ($this->allTypes as $searchType) {
            if ($recordType === $searchType->getSearchGroup()) {
                $result[] = $searchType;
            }
        }
        return $result;
    }
}
