<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Vanilla\Cloud\ElasticSearch\Driver\ElasticSearchQuery;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\BoostableSearchQueryInterface;
use Vanilla\Search\CollapsableSerachQueryInterface;
use Vanilla\Search\MysqlSearchQuery;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchResultItem;
use Vanilla\Search\SearchTypeQueryExtenderInterface;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Models\CrawlableRecordSchema;

/**
 * Search record type for a discussion.
 */
class DiscussionSearchType extends AbstractSearchType {

    /** @var \DiscussionsApiController */
    protected $discussionsApi;

    /** @var \CategoryModel */
    protected $categoryModel;

    /** @var \UserModel $userModel */
    protected $userModel;

    /** @var \TagModel */
    protected $tagModel;

    /** @var BreadcrumbModel */
    protected $breadcrumbModel;

    /** @var array extenders */
    protected $extenders = [];

    /**
     * DI.
     *
     * @param \DiscussionsApiController $discussionsApi
     * @param \CategoryModel $categoryModel
     * @param \UserModel $userModel
     * @param \TagModel $tagModel
     * @param BreadcrumbModel $breadcrumbModel
     */
    public function __construct(
        \DiscussionsApiController $discussionsApi,
        \CategoryModel $categoryModel,
        \UserModel $userModel,
        \TagModel $tagModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        $this->discussionsApi = $discussionsApi;
        $this->categoryModel = $categoryModel;
        $this->userModel = $userModel;
        $this->tagModel = $tagModel;
        $this->breadcrumbModel = $breadcrumbModel;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'discussion';
    }

    /**
     * Register search query extender
     *
     * @param SearchTypeQueryExtenderInterface $extender
     */
    public function registerQueryExtender(SearchTypeQueryExtenderInterface $extender) {
        $this->extenders[] = $extender;
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'discussion';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'discussion';
    }

    /**
     * @return bool
     */
    public function supportsCollapsing(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array {
        if ($query->supportsExtenders()) {
            foreach ($this->extenders as $extender) {
                $extender->extendPermissions();
            }
        }
        try {
            $results = $this->discussionsApi->index([
                'discussionID' => implode(",", $recordIDs),
                'limit' => 100,
                'expand' => [ModelUtils::EXPAND_CRAWL, 'tagIDs'],
            ]);
            $results = $results->getData();

            if (!$results) {
                return [];
            }

            $resultItems = array_map(function ($result) {
                $mapped = ArrayUtils::remapProperties($result, [
                    'recordID' => 'discussionID',
                ]);
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['legacyType'] = $this->getSingularLabel();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($mapped['categoryID']));
                return new DiscussionSearchResultItem($mapped);
            }, $results);
            return $resultItems;
        } catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }
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

            $name = $query->getQueryParameter('name');
            if ($name) {
                $query->whereText($name, ['name'], $query::MATCH_FULLTEXT_EXTENDED, $locale);
            }

            $allTextQuery = $query->getQueryParameter('query');
            if ($allTextQuery) {
                $query->whereText($allTextQuery, ['name', 'body'], $query::MATCH_FULLTEXT_EXTENDED, $locale);
            }

            if ($discussionID = $query->getQueryParameter('discussionID', false)) {
                $query->setFilter('DiscussionID', [$discussionID]);
            };
            $categoryIDs = $this->getCategoryIDs($query);
            if (!empty($categoryIDs)) {
                $query->setFilter('CategoryID', $categoryIDs);
            }

            if ($query->supportsExtenders()) {
                /** @var SearchTypeQueryExtenderInterface $extender */
                foreach ($this->extenders as $extender) {
                    $extender->extendQuery($query);
                }
            }

            if ($query instanceof BoostableSearchQueryInterface && $query->getBoostParameter('discussionRecency')) {
                $query->startBoostQuery();
                $query->boostFieldRecency('dateInserted');
                $query->boostType($this, $this->getBoostValue());
                $query->endBoostQuery();
            }

            // tags
            // Notably includes 0 to still allow other normalized records if set.
            $tagNames = $query->getQueryParameter('tags', []);
            $tagIDs = $this->tagModel->getTagIDsByName($tagNames);
            $tagOp = $query->getQueryParameter('tagOperator', 'or');
            if (!empty($tagIDs)) {
                if ($query instanceof ElasticSearchQuery) {
                    $query->setFilter('tagIDs', $tagIDs, false, $tagOp);
                } else {
                    $query->setFilter('Tags', $tagIDs, false, $tagOp);
                }
            }
        }
    }

    /**
     * @return float|null
     */
    protected function getBoostValue(): ?float {
        return 0.5;
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema {
        return Schema::parse([
            'discussionID:i?' => [
                'x-search-scope' => true,
            ],
            'categoryID:i?' => [
                'x-search-scope' => true,
            ],
            'categoryIDs:a?' => [
                'items' => [
                    'type' => 'integer',
                ],
                'x-search-scope' => true,
            ],
            'followedCategories:b?' => [
                'x-search-filter' => true,
            ],
            'includeChildCategories:b?' => [
                'x-search-filter' => true,
            ],
            'includeArchivedCategories:b?' => [
                'x-search-filter' => true,
            ],
            'tags:a?' => [
                'items' => [
                    'type' => 'string',
                ],
                'x-search-filter' => true,
            ],
            'tagOperator:s?' => [
                'items' => [
                    'type' => 'string',
                    'enum' => [SearchQuery::FILTER_OP_OR, SearchQuery::FILTER_OP_AND],
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchemaExtension(): Schema {
        return Schema::parse([
            "sort:s?" => [
                "enum" => [
                    "score",
                    "-score",
                    "hot",
                    "-hot"
                ],
            ]
        ]);
    }

    /**
     * Get article boost types.
     *
     * @return Schema|null
     */
    public function getBoostSchema(): ?Schema {
        return Schema::parse([
            'discussionRecency:b' => [
                'default' => true,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
        // Validate category IDs.
        $categoryID = $query->getQueryParameter('categoryID', null);
        if ($categoryID !== null && !$this->categoryModel::checkPermission($categoryID, 'Vanilla.Discussions.View')) {
            throw new PermissionException('Vanilla.Discussions.View');
        }
        $categoryIDs = $query->getQueryParameter('categoryIDs', null);
        if ($categoryID !== null && $categoryIDs !== null) {
            $validation = new Validation();
            $validation->addError('categoryID', 'Only one of categoryID, categoryIDs are allowed.');
            throw new ValidationException($validation);
        }
    }

    /**
     * Generates prepares sql query string
     *
     * @param MysqlSearchQuery $query
     * @return string
     */
    public function generateSql(MysqlSearchQuery $query): string {
        /** @var \Gdn_SQLDriver $db */
        $db = clone $query->getDB();

        $categoryIDs = $this->getCategoryIDs($query);

        if ($categoryIDs === []) {
            return '';
        }

        // Build base query
        $db->from('Discussion d')
            ->select('d.DiscussionID as recordID, d.Name as Title, d.Format, d.CategoryID, d.Score')
            ->select('d.DiscussionID', "concat('/discussion/', %s)", 'Url')
            ->select('d.DateInserted')
            ->select('d.Type as recordType')
            ->select('d.InsertUserID as UserID')
            ->select("'discussion'", '', 'type')
            ->orderBy('d.DateInserted', 'desc')
        ;
        if (false !== $query->get('expandBody', null)) {
            $db->select('d.Body as body');
        }

        $terms = $query->get('query', false);
        if ($terms) {
            $terms = $db->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $terms).'%');
            $db->beginWhereGroup();
            foreach (['d.Name', 'd.Body'] as $field) {
                $db->orWhere("$field like", $terms, true, false);
            }
            $db->endWhereGroup();
        }

        if ($name = $query->get('name', false)) {
            $db->where('d.Name like', $db->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $name).'%'), true, false);
        }

        $this->applyUserIDs($db, $query, 'd');
        $this->applyDateInsertedSql($db, $query, 'd');

        $discussionID = $query->get('discussionID', false);
        if ($discussionID !== false) {
            $db->where('d.DiscussionID', $discussionID);
        }

        if (!empty($categoryIDs)) {
            $db->whereIn('d.CategoryID', $categoryIDs);
        }

        $limit = $query->get('limit', 100);
        $offset = $query->get('offset', 0);
        $db->limit($limit + $offset);

        $sql = $db->getSelect(true);
        $db->reset();

        return $sql;
    }

    /**
     * Apply the dateInserted parameters.
     *
     * @param \Gdn_SQLDriver $sql
     * @param MysqlSearchQuery $query
     * @param string $tableAlias
     */
    protected function applyDateInsertedSql(\Gdn_SQLDriver $sql, MysqlSearchQuery $query, string $tableAlias) {
        $dateInserted = $query->getQueryParameter('dateInserted');

        if ($dateInserted) {
            $schema = new DateFilterSchema();
            $sql->where(DateFilterSchema::dateFilterField("$tableAlias.DateInserted", $schema->validate($dateInserted)));
        }
    }

    /**
     * Apply the insertUsers part of the SQL query.
     *
     * @param \Gdn_SQLDriver $sql
     * @param MysqlSearchQuery $query
     * @param string $tableAlias
     */
    protected function applyUserIDs(\Gdn_SQLDriver $sql, MysqlSearchQuery $query, string $tableAlias) {
        $insertUserIDs = $query->getQueryParameter('insertUserIDs', false);
        $insertUserNames = $query->getQueryParameter('insertUserNames', false);
        if (!$insertUserIDs && $insertUserNames) {
            $users = $this->userModel->getWhere([
                'name' => $insertUserNames,
            ])->resultArray();
            $insertUserIDs = array_column($users, 'UserID');
        }

        if ($insertUserIDs) {
            $sql->where("$tableAlias.InsertUserID", $insertUserIDs);
        }
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
            $query->getQueryParameter('includeArchivedCategories'),
            $query->getQueryParameter('categoryIDs')
        );
        if ($query->supportsExtenders()) {
            /** @var SearchTypeQueryExtenderInterface $extender */
            foreach ($this->extenders as $extender) {
                $categoryIDs = $extender->extendCategories($categoryIDs);
            }
        }
        return $categoryIDs;
    }

    /**
     * Get user ids by their name if query has insertUserNames argument
     *
     * @param array $userNames
     * @return array|null
     */
    protected function getUserIDs(array $userNames): ?array {
        if (!empty($userNames)) {
            $users = $this->userModel->getWhere([
                'name' => $userNames,
            ])->resultArray();
            $userIDs = array_column($users, 'UserID');
            return $userIDs;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getSingularLabel(): string {
        return \Gdn::translate('Discussion');
    }

    /**
     * @return string
     */
    public function getPluralLabel(): string {
        return \Gdn::translate('Discussions');
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array {
        return [0];
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int {
        return ($guid - 1) / 10;
    }
}
