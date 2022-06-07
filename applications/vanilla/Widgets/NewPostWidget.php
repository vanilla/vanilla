<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\InjectableInterface;
use Gdn;
use CategoryModel;
use Vanilla\Layout\Section\SectionThreeColumns;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;
use Vanilla\Widgets\React\SectionAwareInterface;

/**
 * New Post Button Widget
 */
class NewPostWidget implements
    ReactWidgetInterface,
    CombinedPropsWidgetInterface,
    InjectableInterface,
    SectionAwareInterface
{
    use HomeWidgetContainerSchemaTrait, CombinedPropsWidgetTrait;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * DI.
     *
     * @param CategoryModel $categoryModel
     */
    public function setDependencies(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "New Post Button";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "newpost";
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "NewPostMenu";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/newpostbutton.svg";
    }

    /**
     * @return array
     */
    public static function getRecommendedSectionIDs(): array
    {
        return [SectionTwoColumns::getWidgetID(), SectionThreeColumns::getWidgetID()];
    }

    /**
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array
    {
        //basic permission
        $hasPermission = Gdn::session()->checkPermission("Vanilla.Discussions.Add", true, "Category", "any");

        //if no permissions or guest mode
        if (!$hasPermission || !Gdn::session()->isValid()) {
            return [];
        }

        //get allowed discussion type data
        $permissionCategory = $this->categoryModel::permissionCategory(null);
        $allowedDiscussionTypes = $this->categoryModel::getAllowedDiscussionData($permissionCategory, []);

        $this->props["items"] = [];

        //permission check and generate desired format for items
        foreach ($allowedDiscussionTypes as $key => $discussionType) {
            if (
                isset($discussionType["AddPermission"]) &&
                !Gdn::session()->checkPermission($discussionType["AddPermission"])
            ) {
                unset($allowedDiscussionTypes[$key]);
                continue;
            }

            array_push($this->props["items"], [
                "label" => $discussionType["AddText"],
                "action" => $discussionType["AddUrl"],
                "type" => "link",
                "id" => str_replace(" ", "-", strtolower($discussionType["AddText"])),
                "icon" => $discussionType["AddIcon"],
                "asOwnButton" => in_array(strtolower($key), $this->props["asOwnButtons"]),
            ]);
        }

        return $this->props;
    }

    /**
     * Get the schema specific to this widget.
     *
     * @return Schema
     */
    public static function widgetSpecificSchema(): Schema
    {
        //prefilter allowed discussions for asOwnButton dropdown
        $permissionCategory = CategoryModel::permissionCategory(false);
        $globalAllowedDiscussions = CategoryModel::getAllowedDiscussionData($permissionCategory, []);
        $asOwnButtonFormChoices = [];
        foreach (array_keys($globalAllowedDiscussions) as $allowedDiscussion) {
            $asOwnButtonFormChoices[strtolower($allowedDiscussion)] = $allowedDiscussion;
        }

        return Schema::parse([
            "asOwnButtons?" => [
                "type" => "array",
                "description" => "List of separate button types (not in the dropdown).",
                "items" => [
                    "type" => "string",
                ],
                "default" => [],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Separate Buttons",
                        "These buttons will appear as separate buttons instead of dropdown option."
                    ),
                    new StaticFormChoices($asOwnButtonFormChoices),
                    null,
                    true
                ),
            ],
            "borderRadius:i?" => [
                "description" => "New Post Button border radius",
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Border Radius", "Set border radius for the button.")
                ),
            ],
            "containerOptions:?" => Schema::parse([
                "outerBackground:?" => Schema::parse([
                    "color:?" => [
                        "description" => "Set the background color of the component.",
                        "x-control" => SchemaForm::color(
                            new FormOptions("Background color", "Pick a background color.")
                        ),
                    ],
                ]),
                "borderType:s?" => [
                    "enum" => self::borderTypeOptions(),
                    "description" => "Describe what type of border the widget should have.",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Border Type", "Choose widget border type", "Style Guide Default"),
                        new StaticFormChoices([
                            "border" => "Border",
                            "separator" => "Separator",
                            "none" => "None",
                            "shadow" => "Shadow",
                        ])
                    ),
                ],
                "headerAlignment:s?" => [
                    "description" => "Configure alignment of the title, subtitle, and description.",
                    "enum" => ["left", "center"],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions(
                            "Header Alignment",
                            "Configure alignment of the title, subtitle, and description."
                        ),
                        new StaticFormChoices(["left" => "Left", "center" => "Center"])
                    ),
                ],
            ])
                ->setDescription("Configure various container options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Container Options"))),
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(self::widgetTitleSchema(), self::widgetSpecificSchema());
    }
}
