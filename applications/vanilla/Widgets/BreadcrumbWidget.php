<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Models\GenericRecord;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class BreadcrumbWidget extends AbstractReactModule implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use HomeWidgetContainerSchemaTrait;

    /**
     * @var BreadcrumbModel
     */
    private BreadcrumbModel $breadcrumbModel;

    /**
     * @var SiteSectionModel
     */
    private SiteSectionModel $siteSectionModel;

    /**
     * DI
     *
     * @param BreadcrumbModel $breadcrumbModel
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(BreadcrumbModel $breadcrumbModel, SiteSectionModel $siteSectionModel)
    {
        parent::__construct();
        $this->breadcrumbModel = $breadcrumbModel;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * @return array|null
     * @throws ClientException
     */
    public function getProps(): ?array
    {
        $hydrateCrumbs = $this->getHydrateParam("breadcrumbs");
        if (!empty($hydrateCrumbs)) {
            return ["children" => $hydrateCrumbs];
        }

        // We always want a home breadcrumb
        $crumbs = [new Breadcrumb(t("Home"), "/")];

        $recordType = "";
        $layoutViewType = $this->getHydrateParam("layoutViewType");
        $categoryViewTypes = ["categoryList", "discussionCategoryPage", "nestedCategoryList"];
        if (in_array($layoutViewType, $categoryViewTypes)) {
            $recordType = "category";
        }

        // Always show the discussion breadcrumb if on the discussion list page
        if ($layoutViewType === "discussionList") {
            $recordType = "discussion";
            $discussionCrumb = new Breadcrumb(t("Discussions"), "/discussions");
            $crumbs[] = $discussionCrumb;
        }

        // Category Breadcrumbs
        $categoryID = $this->getHydrateParam("category.categoryID");
        if ($recordType === "category" && $categoryID) {
            try {
                $crumbs = $this->breadcrumbModel->getForRecord(new GenericRecord($recordType, $categoryID));
            } catch (\Vanilla\Navigation\BreadcrumbProviderNotFoundException $e) {
                throw new ClientException("Unable to get breadcrumbs.");
            }
        }

        // Group breadcrumbs
        $groupID = $this->getHydrateParam("groupID");
        if ($layoutViewType === "createPost" && $groupID) {
            try {
                $crumbs = $this->breadcrumbModel->getForRecord(new GenericRecord("group", $groupID));
            } catch (\Vanilla\Navigation\BreadcrumbProviderNotFoundException $e) {
                throw new ClientException("Unable to get breadcrumbs.");
            }
        }

        return ["children" => $crumbs];
    }

    /**
     * @return string
     */
    public static function getComponentName(): string
    {
        return "Breadcrumbs";
    }

    /**
     * @return \Garden\Schema\Schema
     */
    public static function getWidgetSchema(): \Garden\Schema\Schema
    {
        return Schema::parse([]);
    }

    /**
     * @return string
     */
    public static function getWidgetName(): string
    {
        return "Breadcrumbs";
    }

    public function renderSeoHtml(array $props): ?string
    {
        $crumbs = $this->breadcrumbModel->crumbsAsArray($props["children"]);

        $result = $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($crumbs));
        return $result;
    }
}
