<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Search;

use CommentsApiController;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Search\AbstractSearchIndexTemplate;
use Vanilla\Utility\ModelUtils;

/**
 * Class CommentSearchIndexTemplate
 *
 * @package Vanilla\Forum\Search
 */
class CommentSearchIndexTemplate extends AbstractSearchIndexTemplate {

    /**
     * @var string $name
     */
    private $searchIndexName = 'comment';

    /**
     * @var CommentsApiController $commentsApiController
     */
    private $commentsApiController;

    /**
     * DiscussionSearchIndexTemplate constructor.
     *
     * @param CommentsApiController $commentsApiController
     */
    public function __construct(CommentsApiController $commentsApiController) {
        $this->commentsApiController = $commentsApiController;
    }

    /**
     * @inheritdoc
     */
    public function getTemplate(): array {
        $schema = CrawlableRecordSchema::applyExpandedSchema(
            $this->commentsApiController->commentSchema(),
            'comment',
            [ModelUtils::EXPAND_CRAWL]
        );
        return $this->convertSchema($schema);
    }

    /**
     * @inheritdoc
     */
    public function getTemplateName(): string {
        return $this->searchIndexName;
    }
}
