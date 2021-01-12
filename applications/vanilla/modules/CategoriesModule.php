<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2008-2020 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Widgets\AbstractHomeWidgetModule;

/**
 * Module displaying Categories.
 */
class CategoriesModule extends AbstractHomeWidgetModule {

    const CATEGORIES_MODULE = "Categories";

    /** @var \CategoriesApiController */
    private $categoriesApi;

    /** @var SiteSectionInterface */
    private $currentSiteSection;

    /**
     * Parameters to pass to the categories API.
     */
    public $apiParams = [];

    /**
     * CategoriesModule constructor.
     *
     * @param \CategoriesApiController $categoriesApi
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        \CategoriesApiController $categoriesApi,
        SiteSectionModel $siteSectionModel
    ) {
        parent::__construct();
        $this->categoriesApi = $categoriesApi;
        $this->currentSiteSection = $siteSectionModel->getCurrentSiteSection();
        $this->moduleName = self::CATEGORIES_MODULE;
    }

    /**
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     * @return array|null
     */
    protected function getData(): ?array {
        $contextualCategoryID = $this->currentSiteSection->getAttributes()['categoryID'] ?? -1;
        $apiParams = array_merge([
            'limit' => 10,
            'featured' => true,
            'parentCategoryID' => $contextualCategoryID,
        ], $this->apiParams);
        $data = $this->categoriesApi->index($apiParams)->getData();
        return array_map([$this, 'mapCategoryToItem'], $data);
    }

    /**
     * Return the module's title
     *
     * @return string|null
     */
    protected function getTitle(): ?string {
        return $this->title ?: t('Featured Categories');
    }


    /**
     * Utility for for mapping category data into a widget item.
     *
     * @param array $category
     * @return array
     */
    public function mapCategoryToItem(array $category): array {
        return [
            'to' => $category['url'],
            'iconUrl' => $category['iconUrl'],
            'imageUrl' => $category['bannerUrl'],
            'name' => $category['name'],
            'description' => $category['description'],
            'counts' => [
                [
                    'labelCode' => 'Discussions',
                    'count' => $category['countAllDiscussions'],
                ]
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "Featured Categories";
    }
}
