<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Schema\Schema;
use Garden\Web\Exception\HttpException;
use Vanilla\Adapters\SphinxClient;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\MysqlSearchQuery;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchResultItem;
use Vanilla\Sphinx\Search\SphinxSearchQuery;
use Vanilla\Utility\ArrayUtils;

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

    /**
     * DI.
     *
     * @param \DiscussionsApiController $discussionsApi
     * @param \CategoryModel $categoryModel
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
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs): array {
        try {
            $results = $this->discussionsApi->index([
                'discussionID' => implode(",", $recordIDs),
                'limit' => 100,
            ]);
            $results = $results->getData();

            $resultItems = array_map(function ($result) {
                $mapped = ArrayUtils::remapProperties($result, [
                    'recordID' => 'discussionID',
                ]);
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($mapped['categoryID']));
                return new SearchResultItem($mapped);
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
        $types = $query->getQueryParameter('types');
        if ($types !== null && ((count($types) > 0) && !in_array($this->getSearchGroup(), $types))) {
            // discussions are not the part of this search query request
            // we don't need to do anything
            return;
        }

        $types = $query->getQueryParameter('recordTypes');
        if ($types !== null && ((count($types) > 0) && !in_array($this->getType(), $types))) {
            // discussions are not the part of this search query request
            // we don't need to do anything
            return;
        }
        // Notably includes 0 to still allow other normalized records if set.
        $tagNames = $query->getQueryParameter('tags', []);
        $tagIDs = $this->tagModel->getTagIDsByName($tagNames);
        $tagOp = $query->getQueryParameter('tagOperator', 'or');
        ;

        if ($query instanceof SphinxSearchQuery) {
            // TODO: Figure out the ideal time to do this.
            // Make sure we don't get duplicate discussion results.
            // $query->setGroupBy('DiscussionID', SphinxClient::GROUPBY_ATTR, 'sort DESC');
            // Always set.
            // discussionID
            if ($discussionID = $query->getQueryParameter('discussionID', false)) {
                $query->setFilter('DiscussionID', [$discussionID]);
            };
            $categoryIDs = $this->getCategoryIDs($query);
            if (!empty($categoryIDs)) {
                $query->setFilter('CategoryID', $categoryIDs);
            } else {
                // Only include non-category content.
                $query->setFilter('CategoryID', [0]);
            }

            // tags
            if (!empty($tagIDs)) {
                $query->setFilter('Tags', $tagIDs, false, $tagOp);
            }
        } elseif ($query instanceof MysqlSearchQuery) {
             $query->addSql($this->generateSql($query));
        }
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
        return $this->schemaWithTypes(Schema::parse([
            'discussionID:i?' => [
                'x-search-scope' => true,
            ],
            'categoryID:i?' => [
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
        ]));
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
    }

    /**
     * Generates prepares sql query string
     *
     * @param MysqlSearchQuery $query
     * @return string
     */
    public function generateSql(MysqlSearchQuery $query): string {
        /** @var \Gdn_SQLDriver $db */
        $db = $query->getDB();
        $db->reset();

        $categoryIDs = $this->getCategoryIDs($query);

        if ($categoryIDs === []) {
            return '';
        }

        $userIDs = $this->getUserIDs($query->get('insertUserNames', []));

        if ($userIDs === []) {
            return '';
        }

        $db->reset();

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
                $db->orWhere("$field like", $terms, false, false);
            }
            $db->endWhereGroup();
        }

        if ($title = $query->get('title', false)) {
            $db->where('d.Name like', $db->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $title).'%'));
        }

        if ($users = $query->get('users', false)) {
            $author = array_column($users, 'UserID');
            $db->where('d.InsertUserID', $author);
        }

        if ($users = $query->get('insertUserIds', false)) {
            $author = array_column($users, 'UserID');
            $db->where('d.InsertUserID', $author);
        }

        if (is_array($userIDs)) {
            $db->where('d.InsertUserID', $userIDs);
        }

        if ($discussionID = $query->get('discussionID', false)) {
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
}
