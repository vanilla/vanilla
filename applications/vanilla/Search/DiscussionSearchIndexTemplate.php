<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Search;

use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Search\AbstractSearchIndexTemplate;
use Vanilla\Utility\ModelUtils;

/**
 * Class DiscussionSearchIndexTemplate
 *
 * @package Vanilla\Forum\Search
 */
class DiscussionSearchIndexTemplate extends AbstractSearchIndexTemplate {

    /**
     * @var string $name
     */
    private $searchIndexName = 'discussion';

    /**
     * @var \DiscussionsApiController $discussionsApiController
     */
    private $discussionsApiController;

    /**
     * DiscussionSearchIndexTemplate constructor.
     *
     * @param \DiscussionsApiController $discussionsApiController
     */
    public function __construct(\DiscussionsApiController $discussionsApiController) {
        $this->discussionsApiController = $discussionsApiController;
    }

    /**
     * @inheritdoc
     */
    public function getTemplate(): array {
        $schema = CrawlableRecordSchema::applyExpandedSchema(
            $this->discussionsApiController->discussionSchema(),
            'discussion',
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
