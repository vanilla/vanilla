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

            $resultItems = array_map(function ($result) use ($categoryIDsByCommentIDs) {
                $mapped = ArrayUtils::remapProperties($result, [
                    'recordID' => 'commentID',
                ]);
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['breadcrumbs'] = $this->breadcrumbModel->getForRecord(
                    new ForumCategoryRecordType(
                        $categoryIDsByCommentIDs[$result['commentID']]
                    )
                );
                return new SearchResultItem($mapped);
            }, $results);
            return $resultItems;
        } catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }
    }
}
