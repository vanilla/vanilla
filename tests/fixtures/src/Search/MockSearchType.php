<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Search;

use Garden\Schema\Schema;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchQuery;

/**
 * Mock search type for tests.
 */
class MockSearchType extends AbstractSearchType {

    /** @var string */
    private $key;

    /** @var string */
    private $searchGroup;

    /** @var string */
    private $type;

    /** @var array */
    private $sorts = [];

    /** @var Schema */
    private $querySchema;

    /** @var bool */
    private $userHasPermission = true;

    /** @var bool */
    private $isExclusiveType = false;

    /**
     * DI.
     *
     * @param string $type
     */
    public function __construct(string $type = 'mock') {
        $this->key = $type;
        $this->searchGroup = $type;
        $this->type = $type;
        $this->querySchema = Schema::parse([]);
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getSearchGroup(): string {
        return $this->searchGroup;
    }

    /**
     * @param string $searchGroup
     */
    public function setSearchGroup(string $searchGroup): void {
        $this->searchGroup = $searchGroup;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getSorts(): array {
        return $this->sorts;
    }

    /**
     * @param array $sorts
     */
    public function setSorts(array $sorts): void {
        $this->sorts = $sorts;
    }

    /**
     * @return Schema
     */
    public function getQuerySchema(): Schema {
        return $this->querySchema;
    }

    /**
     * @param Schema $querySchema
     */
    public function setQuerySchema(Schema $querySchema): void {
        $this->querySchema = $querySchema;
    }

    /**
     * @return bool
     */
    public function userHasPermission(): bool {
        return $this->userHasPermission;
    }

    /**
     * @param bool $userHasPermission
     */
    public function setUserHasPermission(bool $userHasPermission): void {
        $this->userHasPermission = $userHasPermission;
    }

    /**
     * @return bool
     */
    public function isExclusiveType(): bool {
        return $this->isExclusiveType;
    }

    /**
     * @param bool $isExclusiveType
     */
    public function setIsExclusiveType(bool $isExclusiveType): void {
        $this->isExclusiveType = $isExclusiveType;
    }

    /**
     * Stubbed because it's a mock.
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array {
        return [];
    }

    /**
     * Stubbed because it's a mock.
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query) {
    }

    /**
     * Stubbed because it's a mock.
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
    }

    /**
     * @inheritdoc
     */
    public function getSingularLabel(): string {
        return 'Mock';
    }

    /**
     * @inheritdoc
     */
    public function getPluralLabel(): string {
        return 'Mocks';
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int {
        return null;
    }
}
