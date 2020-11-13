<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Search;

use CategoriesApiController;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Search\AbstractSearchIndexTemplate;
use Vanilla\Utility\ModelUtils;

/**
 * Class CategorySearchIndexTemplate
 *
 * @package Vanilla\Forum\Search
 */
class CategorySearchIndexTemplate extends AbstractSearchIndexTemplate {

    /**
     * @var string $name
     */
    private $searchIndexName = 'category';

    /**
     * @var CategoriesApiController $categoriesApiController
     */
    private $categoriesApiController;

    /**
     * DiscussionSearchIndexTemplate constructor.
     *
     * @param CategoriesApiController $categoriesApiController
     */
    public function __construct(CategoriesApiController $categoriesApiController) {
        $this->categoriesApiController = $categoriesApiController;
    }

    /**
     * @inheritdoc
     */
    public function getTemplate(): array {
        $schema = CrawlableRecordSchema::applyExpandedSchema(
            $this->categoriesApiController->schemaWithChildren(),
            'category',
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
