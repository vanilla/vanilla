<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Schema\Schema;
use Vanilla\Search\BoostableSearchQueryInterface;
use Vanilla\Search\MysqlSearchQuery;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchResultItem;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Models\CrawlableRecordSchema;

/**
 * Search record type for a user.
 */
class CategorySearchType extends AbstractSearchType {

    /** @var \CategoryModel $categoryModel */
    protected $categoryModel;

    /** @var \CategoriesApiController $categoriesApi */
    protected $categoriesApi;

    private $excludedCategoryIDs = [];

    /**
     * CategorySearchType constructor.
     *
     * @param \CategoryModel $categoryModel
     * @param \CategoriesApiController $categoriesApi
     */
    public function __construct(
        \CategoryModel $categoryModel,
        \CategoriesApiController $categoriesApi
    ) {
        $this->categoryModel = $categoryModel;
        $this->categoriesApi = $categoriesApi;
    }

    /**
     * Apply some categoryIDs that should be excluded from category searches.
     *
     * @param array $ids
     */
    public function addExcludedCategoryIDs(array $ids) {
        $this->excludedCategoryIDs = array_unique(array_merge($ids, $this->excludedCategoryIDs));
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'category';
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'category';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'category';
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array {
        $filteredRecordIDs = [];
        foreach ($recordIDs as $categoryID) {
            if (!in_array($categoryID, $this->excludedCategoryIDs)) {
                $filteredRecordIDs[] = $categoryID;
            }
        }
        if (count($filteredRecordIDs) === 0) {
            return [];
        }

        $results = $this->categoriesApi->index(
            [
                'categoryID' => implode(',', $filteredRecordIDs),
                'expand' => [ModelUtils::EXPAND_CRAWL],
            ],
            false
        );
        $results = $results->getData();

        $resultItems = array_map(function ($result) {
            $mapped = ArrayUtils::remapProperties($result, [
                'recordID' => 'categoryID',
            ]);
            $mapped['recordType'] = $this->getSearchGroup();
            $mapped['type'] = $this->getType();
            $this->mapCounts($mapped);

            $categoryResultItem = new SearchResultItem($mapped);

            return $categoryResultItem;
        }, $results);

        return $resultItems;
    }

    /**
     * Map the count fields of a record.
     *
     * @param array $record
     */
    private function mapCounts(array &$record) {
        $record['counts'] = [
            [
                'labelCode' => 'sub-categories',
                'count' => $record['countCategories'],
            ],
            [
                'labelCode' => 'discussions',
                'count' => $record['countAllDiscussions'],
            ]
        ];
    }

    /**
     * Overridden to map ocunts.
     * @inheritdoc
     */
    public function convertForeignSearchItem(array $record): SearchResultItem {
        $this->mapCounts($record);
        return parent::convertForeignSearchItem($record);
    }

    /**
     * Get category ids from DB if query has it as a filter
     *
     * @param SearchQuery $query
     * @return array|null
     */
    protected function getCategoryIDs(SearchQuery $query): ?array {
        $categoryIDs = $this->categoryModel->getSearchCategoryIDs(
            $query->getQueryParameter('categoryID'),
            $query->getQueryParameter('followedCategories'),
            $query->getQueryParameter('includeChildCategories'),
            $query->getQueryParameter('includeArchivedCategories')
        );
        if (empty($categoryIDs)) {
            $categoryIDs[] = 0;
        }

        return $categoryIDs;
    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query) {
        if ($query instanceof MysqlSearchQuery) {
            $query->addSql($this->generateSql($query));
        } else {
            $query->addIndex($this->getIndex());

            $locale = $query->getQueryParameter('locale');
            $enableBoost = false;

            if ($queryParam = $query->getQueryParameter('query', false)) {
                $query->whereText($queryParam, ['name', 'description'], $query::MATCH_FULLTEXT_EXTENDED, $locale);
                $enableBoost = true;
            }

            if ($name = $query->getQueryParameter('name', false)) {
                $query->whereText($name, ['name'], $query::MATCH_FULLTEXT_EXTENDED, $locale);
                $enableBoost = true;
            }

            if ($description = $query->getQueryParameter('description', false)) {
                $query->whereText($description, ['description']);
            }

            $categoryIDs = $this->getCategoryIDs($query);
            if (!empty($categoryIDs)) {
                $query->setFilter('CategoryID', $categoryIDs);
            }

            if ($enableBoost) {
                if ($query instanceof BoostableSearchQueryInterface && $query->getBoostParameter('categoryBoost')) {
                    $query->startBoostQuery();
                    $query->boostType($this, $this->getBoostValue());
                    $query->endBoostQuery();
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array {
        return [

        ];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema {
        return Schema::parse([
            'description:s?' => [
                'x-search-filter' => true,
            ],
        ]);
    }

    /**
     * Get article boost types.
     *
     * @return Schema|null
     */
    public function getBoostSchema(): ?Schema {
        return Schema::parse([
            'categoryBoost:b' => [
                'default' => true,
            ],
        ]);
    }

    /**
     * Get search type boost value.
     *
     * @return float|null
     */
    protected function getBoostValue(): ?float {
        return 10;
    }


    /**
     * Generates prepares sql query string
     *
     * @param MysqlSearchQuery $query
     * @return string
     */
    public function generateSql(MysqlSearchQuery $query): string {
        // mysql is not implemented
        return '';
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
        ;
    }

    /**
     * @return string
     */
    public function getSingularLabel(): string {
        return \Gdn::translate('Category');
    }

    /**
     * @return string
     */
    public function getPluralLabel(): string {
        return \Gdn::translate('Categories');
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
