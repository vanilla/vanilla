<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
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
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * New Post Button Widget
 */
class NewPostWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, InjectableInterface
{
    use HomeWidgetContainerSchemaTrait;
    use CombinedPropsWidgetTrait;
    use DefaultSectionTrait;

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
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array
    {
        $customLabels = $this->props["customLabels"] ?? [];
        $excludedButtons = $this->props["excludedButtons"] ?? [];

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
        foreach ($allowedDiscussionTypes as $key => $discussionType) {
            if (
                isset($discussionType["AddPermission"]) &&
                !Gdn::session()->checkPermission($discussionType["AddPermission"])
            ) {
                // User doesn't have permission.
                continue;
            }

            $isExcluded = in_array(strtolower($key), $excludedButtons);
            if ($isExcluded) {
                // Button was excluded in the configuration.
                continue;
            }

            $this->props["items"][] = [
                "label" => $customLabels[strtolower($key)] ?? $discussionType["AddText"],
                "action" => $discussionType["AddUrl"],
                "type" => "link",
                "id" => str_replace(" ", "-", strtolower($discussionType["AddText"])),
                "icon" => $discussionType["AddIcon"],
                "asOwnButton" => in_array(strtolower($key), $this->props["asOwnButtons"]),
            ];
        }

        $postableDiscussionTypes = CategoryModel::instance()->getPostableDiscussionTypes();
        $this->props["postableDiscussionTypes"] = $postableDiscussionTypes;

        return $this->props;
    }

    /**
     * Get labels schema.
     *
     * @param array|null $postTypes
     * @return Schema
     */
    public static function labelsSchema(?array $postTypes = null): Schema
    {
        //button labels schema
        $labelsSchema = [];
        $labelsSchema["showLabels:b?"] = [
            "description" => "Change button display text.",
            "x-control" => SchemaForm::toggle(new FormOptions("Custom Labels", "Set custom labels for buttons.")),
        ];
        foreach ($postTypes as $key => $type) {
            $labelsSchema[strtolower($key) . ":s?"] = [
                "description" => "Custom label for " . $type["AddText"] . " button.",
                "x-control" => SchemaForm::textBox(
                    new FormOptions($type["AddText"], "Set a custom label for " . $type["AddText"] . " button."),
                    "text",
                    new FieldMatchConditional(
                        "customLabels.showLabels",
                        Schema::parse([
                            "type" => "boolean",
                            "const" => true,
                        ])
                    )
                ),
            ];
        }

        return Schema::parse($labelsSchema);
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
            "excludedButtons:?" => [
                "type" => "array",
                "description" => "List of excluded buttons.",
                "items" => [
                    "type" => "string",
                ],
                "default" => [],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Excluded Buttons", "These button types will be excluded"),
                    new StaticFormChoices($asOwnButtonFormChoices),
                    null,
                    true
                ),
            ],
            "customLabels:?" => self::labelsSchema($globalAllowedDiscussions),
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
