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
use Vanilla\Forum\Modules\FoundationCategoriesShim;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Widgets\AbstractHomeWidgetModule;

/**
 * Module displaying Categories.
 */
class CategoriesModule extends AbstractHomeWidgetModule
{
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
     * @var bool
     */
    private $countComments = false;

    /**
     * CategoriesModule constructor.
     *
     * @param \CategoriesApiController $categoriesApi
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(\CategoriesApiController $categoriesApi, SiteSectionModel $siteSectionModel)
    {
        parent::__construct();
        $this->categoriesApi = $categoriesApi;
        $this->currentSiteSection = $siteSectionModel->getCurrentSiteSection();
        $this->moduleName = self::CATEGORIES_MODULE;
        $this->title = t("Featured Categories");
    }

    /**
     * @return string
     */
    public function assetTarget()
    {
        return "Content";
    }

    /**
     * @return bool
     */
    public function isCountComments(): bool
    {
        return $this->countComments;
    }

    /**
     * @param bool $countComments
     */
    public function setCountComments(bool $countComments): void
    {
        $this->countComments = $countComments;
    }

    /**
     * @return array|null
     */
    protected function getData(): ?array
    {
        $contextualCategoryID = $this->currentSiteSection->getAttributes()["categoryID"] ?? -1;
        $apiParams = array_merge(
            [
                "limit" => 10,
                "featured" => true,
                "parentCategoryID" => $contextualCategoryID,
            ],
            $this->apiParams
        );
        $data = $this->categoriesApi->index($apiParams)->getData();
        $itemOptions = [
            "countComments" => $this->isCountComments(),
        ];
        return array_map(function ($item) use ($itemOptions) {
            return FoundationCategoriesShim::mapApiCategoryToItem($item, $itemOptions);
        }, $data);
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Featured Categories";
    }
}
