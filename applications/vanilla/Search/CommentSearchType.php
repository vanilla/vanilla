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
use Vanilla\Search\SearchResultItem;
use Vanilla\Utility\ArrayUtils;

/**
 * Search record type for a discussion.
 */
class CommentSearchType extends DiscussionSearchType {

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
        \TagModel $tagModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        parent::__construct($discussionsApi, $categoryModel, $tagModel, $breadcrumbModel);
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
    public function getResultItems(array $recordIDs): array {
        try {
            $results = $this->commentsApi->index([
                'commentID' => implode(",", $recordIDs),
                'limit' => 100,
            ]);
            $results = $results->getData();

            $resultItems = array_map(function ($result) {
                $mapped = ArrayUtils::remapProperties($result, [
                    'recordID' => 'commentID',
                ]);
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(
                    new ForumCategoryRecordType($mapped['categoryID'])
                );
                return new SearchResultItem($mapped);
            }, $results);
            return $resultItems;
        } catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }
    }

    public function generateSql(MysqlSearchQuery $query): string {
        /** @var \Gdn_SQLDriver $db */
        $db = $query->getDB();
        $db->reset();

        $categoryIDs = $this->getCategoryIDs($query);

        $db->reset();

        // Build base query
        $db->from('Comment c')
            ->select('c.CommentID as PrimaryID, d.Name as Title, c.Body as Summary, c.Format, d.CategoryID, c.Score')
            ->select("'/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID", "concat", 'Url')
            ->select('c.DateInserted')
            ->select('null as `Type`')
            ->select('c.InsertUserID as UserID')
            ->select("'Comment'", '', 'RecordType')
            ->join('Discussion d', 'd.DiscussionID = c.DiscussionID')
            ->orderBy('c.DateInserted', 'desc')
        ;

        $terms = $query->get('query', false);
        if ($terms) {
            $terms = $db->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $terms).'%');
            $db->where("c.Body like", $terms, false, false);
        }

        if ($users = $query->get('users', false)) {
            $author = array_column($users, 'UserID');
            $db->where('d.InsertUserID', $author);
        }

        if ($discussionID = $query->get('discussionid', false)) {
            $db->where('d.DiscussionID', $discussionID);
        }

        if (!empty($categoryIDs)) {
            $db->whereIn('d.CategoryID', $categoryIDs);
        }

        $limit = $query->get('limit', 100);
        $offset = $query->get('offset', 0);
        $db->limit($limit + $offset);

        $sql = $db->getSelect();
        $db->reset();

        return $sql;
    }
}
