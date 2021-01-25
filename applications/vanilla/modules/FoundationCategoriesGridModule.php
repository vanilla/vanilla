<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Vanilla\Community\CategoriesModule;
use Vanilla\Site\SiteSectionModel;

/**
 * Class for shimming the old category page into the new category view.
 * Map the old legacy data into the new react view.
 */
class FoundationCategoriesGridModule extends CategoriesModule {

    /** @var array */
    private $widgetItems = [];

    /**
     * DI.
     *
     * @param \CategoriesApiController $categoriesApi
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(\CategoriesApiController $categoriesApi, SiteSectionModel $siteSectionModel) {
        parent::__construct($categoriesApi, $siteSectionModel);
        $this->noGutter = true;
        $this->title = null;
    }

    /**
     * Map the legacy category data and render it into a view that the can be mounted.
     */
    public function getData(): array {
        return $this->widgetItems;
    }

    /**
     * @param array $widgetItems
     */
    public function setWidgetItems(array $widgetItems): void {
        $this->widgetItems = $widgetItems;
    }
}
