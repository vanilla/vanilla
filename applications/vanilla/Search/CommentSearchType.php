<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Web\Exception\HttpException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\MysqlSearchQuery;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\SearchResultItem;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Search record type for a discussion.
 */
class CommentSearchType extends DiscussionSearchType {

    /**
     * @var string Class used to construct search result items.
     */
    public static $searchItemClass = DiscussionSearchResultItem::class;

    /** @var \CommentsApiController */
    private $commentsApi;

    /** @var \CommentModel */
    private $commentModel;

    /**
     * @inheritdoc
     */
    public function __construct(
        \CommentsApiController $commentsApi,
        \CommentModel $commentModel,
        \DiscussionsApiController $discussionsApi,
        \CategoryModel $categoryModel,
        \UserModel $userModel,
        \TagModel $tagModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        parent::__construct($discussionsApi, $categoryModel, $userModel, $tagModel, $breadcrumbModel);
        $this->commentsApi = $commentsApi;
        $this->commentModel = $commentModel;
    }


    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'comment';
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'comment';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'comment';
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array {
        try {
            $results = $this->commentsApi->index([
                'commentID' => implode(",", $recordIDs),
                'limit' => 100,
                'expand' => [ModelUtils::EXPAND_CRAWL],
            ]);
            $results = $results->getData();

            $resultItems = array_map(function ($result) {
                $mapped = ArrayUtils::remapProperties($result, [
                    'recordID' => 'commentID',
                ]);
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['legacyType'] = $this->getSingularLabel();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(
                    new ForumCategoryRecordType($mapped['categoryID'])
                );
                return new CommentSearchResultItem($mapped);
            }, $results);
            return $resultItems;
        } catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }
    }

    /**
     * @return float|null
     */
    protected function getBoostValue(): ?float {
        return null;
    }

    /**
     * Generates prepares sql query
     *
     * @param MysqlSearchQuery $query
     * @return string
     */
    public function generateSql(MysqlSearchQuery $query): string {
        $types = $query->get('types');

        if ($types !== null && ((count($types) > 0) && !in_array($this->getSearchGroup(), $types))) {
            // discussions are not the part of this search query request
            // we don't need to do anything
            return '';
        }

        $types = $query->get('recordTypes');
        if ($types !== null && ((count($types) > 0) && !in_array($this->getType(), $types))) {
            // discussions are not the part of this search query request
            // we don't need to do anything
            return '';
        }
        /** @var \Gdn_SQLDriver $db */
        $db = $query->getDB();

        $categoryIDs = $this->getCategoryIDs($query);

        if ($categoryIDs === []) {
            return '';
        }

        // Build base query
        $db->from('Comment c')
            ->select('c.CommentID as recordID, d.Name as Title, c.Format, d.CategoryID, c.Score')
            ->select("'/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID", "concat", 'Url')
            ->select('c.DateInserted')
            ->select('null as `recordType`')
            ->select('c.InsertUserID as UserID')
            ->select("'comment'", '', 'type')
            ->join('Discussion d', 'd.DiscussionID = c.DiscussionID')
            ->orderBy('c.DateInserted', 'desc')
        ;

        if (false !== $query->get('expandBody', null)) {
            $db->select('c.Body as body');
        }

        $terms = $query->get('query', false);
        if ($terms) {
            $terms = $db->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $terms).'%');
            $db->where("c.Body like", $terms, false, false);
        }

        if ($name = $query->get('name', false)) {
            $db->where('d.Name like', $db->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $name).'%'), true, false);
        }

        $this->applyUserIDs($db, $query, 'c');
        $this->applyDateInsertedSql($db, $query, 'c');

        if ($discussionID = $query->get('discussionID', false)) {
            $db->where('d.DiscussionID', $discussionID);
        }

        if (!empty($categoryIDs)) {
            $db->where('d.CategoryID', $categoryIDs);
        }

        $limit = $query->get('limit', 100);
        $offset = $query->get('offset', 0);
        $db->limit($limit + $offset);

        $sql = $db->getSelect(true);
        $db->reset();

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function getIndex(): string {
        return 'comment';
    }

    /**
     * @return string
     */
    public function getSingularLabel(): string {
        return \Gdn::translate('Comment');
    }

    /**
     * @return string
     */
    public function getPluralLabel(): string {
        return \Gdn::translate('Comments');
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array {
        return [100];
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int {
        return ($guid - 2) / 10;
    }
}
