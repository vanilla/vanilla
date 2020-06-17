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
        \TagModel $tagModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        $this->discussionsApi = $discussionsApi;
        $this->categoryModel = $categoryModel;
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
        // Notably includes 0 to still allow other normalized records if set.
        $categoryIDs = $this->categoryModel->getSearchCategoryIDs(
            $query->getQueryParameter('categoryID'),
            $query->getQueryParameter('followedCategories'),
            $query->getQueryParameter('includeChildCategories'),
            $query->getQueryParameter('includeArchivedCategories')
        );

        $tagNames = $query->getQueryParameter('tags', []);
        $tagIDs = $this->tagModel->getTagIDsByName($tagNames);
        $tagOp = $query->getQueryParameter('tagOperator', 'or');
        $discussionID = $query->getQueryParameter('discussionID', null);

        // Always set.
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

        // discussionID
        if ($discussionID !== null) {
            $query->setFilter('DiscussionID', [$discussionID]);
        } elseif ($query instanceof SphinxSearchQuery) {
            // TODO: Figure out the ideal time to do this.
            // Make sure we don't get duplicate discussion results.
//            $query->setGroupBy('DiscussionID', SphinxClient::GROUPBY_ATTR, 'sort DESC');
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
}
