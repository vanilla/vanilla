<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Search;

use UsersApiController;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Search\AbstractSearchIndexTemplate;
use Vanilla\Utility\ModelUtils;

/**
 * Class UserSearchIndexTemplate
 *
 * @package Vanilla\Forum\Search
 */
class UserSearchIndexTemplate extends AbstractSearchIndexTemplate {

    /**
     * @var string $name
     */
    private $searchIndexName = 'user';

    /**
     * @var UsersApiController $usersApiController
     */
    private $usersApiController;

    /**
     * DiscussionSearchIndexTemplate constructor.
     *
     * @param UsersApiController $usersApiController
     */
    public function __construct(\UsersApiController $usersApiController) {
        $this->usersApiController = $usersApiController;
    }

    /**
     * @inheritdoc
     */
    public function getTemplate(): array {
        $schema = CrawlableRecordSchema::applyExpandedSchema(
            $this->usersApiController->userSchema(),
            'user',
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
