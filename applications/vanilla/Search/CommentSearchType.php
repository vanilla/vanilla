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
    public function getResultItems(array $recordIDs, array $options = []): array {
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
        $db->reset();

        $categoryIDs = $this->getCategoryIDs($query);

        if ($categoryIDs === []) {
            return '';
        }

        $userIDs = $this->getUserIDs($query->get('insertUserNames', []));

        if ($userIDs === []) {
            return '';
        }

        if (false !== $query->get('expandBody', null)) {
            $db->select('d.Body as body');
        }

        $db->reset();

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

        if ($users = $query->get('users', false)) {
            $author = array_column($users, 'UserID');
            $db->where('c.InsertUserID', $author);
        }

        if (is_array($userIDs)) {
            $db->where('c.InsertUserID', $userIDs);
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
     * @inheritDoc
     */
    public function getIndex(): string {
        return 'comment';
    }
}
