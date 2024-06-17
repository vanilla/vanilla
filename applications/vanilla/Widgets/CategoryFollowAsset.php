<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Widgets;

use CategoriesApiController;
use CategoryModel;
use Garden\Schema\Schema;
use Gdn;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class CategoryFollowAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HomeWidgetContainerSchemaTrait;
    use HydrateAwareTrait;

    /** @var SiteSectionModel */
    private SiteSectionModel $siteSectionModel;

    /** @var CategoryModel */
    private CategoryModel $categoryModel;

    /**
     * DI.
     *
     * @param SiteSectionModel $siteSectionModel
     * @param CategoryModel $categoryModel
     */
    public function __construct(SiteSectionModel $siteSectionModel, CategoryModel $categoryModel)
    {
        $this->siteSectionModel = $siteSectionModel;
        $this->categoryModel = $categoryModel;
    }

    /**
     * @inheritDoc
     * @return string
     */
    public static function getComponentName(): string
    {
        return "CategoryFollowWidget";
    }

    /**
     * @inheritDoc
     * @return string|null
     */
    public static function getWidgetIconPath(): ?string
    {
        return "";
    }

    /**
     * @inheritDoc
     * @return string
     */
    public static function getWidgetName(): string
    {
        return "Follow Category";
    }

    /**
     * @inheritDoc
     * @return string
     */
    public static function getWidgetID(): string
    {
        return "asset.categoryFollow";
    }

    /**
     * @inheritDoc
     * @param array $props
     * @return string|null
     */
    public function renderSeoHtml(array $props): ?string
    {
        return $this->renderWidgetContainerSeoContent(
            $props,
            $this->renderTwigFromString(
                <<<TWIG
You need to Enable Javascript to Follow a Category.
TWIG
                ,
                $props
            )
        );
    }

    /**
     * @inheritDoc
     * @return array|null
     */
    public function getProps(): ?array
    {
        $userID = Gdn::session()->UserID;

        $categoryID = $this->getHydrateParam("category.categoryID");
        $category = $this->categoryModel->getID($categoryID);
        $categoryName = $category->Name;
        $displayAs = $category->DisplayAs;

        // Asset only shows on categories displayed as "Discussions".
        if ($displayAs !== CategoryModel::DISPLAY_DISCUSSIONS) {
            return null;
        }

        $notificationPreferences = $this->categoryModel->getPreferencesByCategoryID($userID, $categoryID);
        $normalizedPreferences = $this->categoryModel->normalizePreferencesOutput($notificationPreferences);
        $emailDigestEnabled = !Gdn::config("Garden.Email.Disabled") && Gdn::config("Garden.Digest.Enabled");
        $isEmailDisabled =
            Gdn::config("Garden.Email.Disabled") || !Gdn::session()->checkPermission("Garden.Email.View");
        return [
            "userID" => $userID,
            "categoryID" => $categoryID,
            "categoryName" => $categoryName,
            "notificationPreferences" => $normalizedPreferences,
            "emailDigestEnabled" => $emailDigestEnabled,
            "emailEnabled" => $isEmailDisabled,
        ] + $this->props;
    }

    /**
     * @inheritDoc
     * @return Schema
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "borderRadius:i?" => [
                "description" => t("Category Follow Button border radius"),
                "x-control" => SchemaForm::textBox(
                    new FormOptions(
                        t("Border Radius"),
                        t("Set border radius for the button."),
                        t("Style Guide default.")
                    ),
                    "number"
                ),
            ],
            "buttonColor:s?" => [
                "description" => t("Category Follow Button background color"),
                "x-control" => SchemaForm::color(
                    new FormOptions(
                        t("Button border color"),
                        t("The color for button border."),
                        t("Style Guide default.")
                    )
                ),
            ],
            "textColor:s?" => [
                "description" => t("Category Follow Button text color"),
                "x-control" => SchemaForm::color(
                    new FormOptions(
                        t("Button text color"),
                        t("The color for the button text."),
                        t("Style Guide default")
                    )
                ),
            ],
            "alignment:s" => [
                "description" => t("The alignment of the Category Follow button"),
                "default" => "end",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        t("Button Alignment"),
                        t("The follow buttons alignment within the panel its placed.")
                    ),
                    new StaticFormChoices([
                        "start" => t("Left"),
                        "center" => t("Middle"),
                        "end" => t("Right"),
                    ])
                ),
            ],
        ]);
    }
}
