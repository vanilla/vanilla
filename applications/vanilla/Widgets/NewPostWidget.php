<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\InjectableInterface;
use Gdn;
use CategoryModel;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Utility\UrlUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidget;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * New Post Button Widget
 */
class NewPostWidget extends ReactWidget implements InjectableInterface
{
    use HomeWidgetContainerSchemaTrait;

    /** @var CategoryModel */
    private CategoryModel $categoryModel;

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
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "New Post Button";
    }

    /**
     * @return string
     */
    public static function getWidgetGroup(): string
    {
        return "Community";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "newpost";
    }

    /**
     * @inheritdoc
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
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function getProps(?array $params = null): ?array
    {
        $customLabels = $this->props["customLabels"] ?? [];
        $excludedButtons = $this->props["excludedButtons"] ?? [];

        $layoutViewType = $this->getHydrateParam("layoutViewType");
        $categoryID = $this->getHydrateParam("category.categoryID") ?? $this->getHydrateParam("categoryID");

        $discussion = $this->getHydrateParam("discussion");
        $groupID = $discussion["groupID"] ?? null;

        $permissionCategory = $this->categoryModel::permissionCategory($categoryID);
        $category = CategoryModel::categories($categoryID);

        if ($categoryID !== -1) {
            // Permission check for the specific category.
            $hasPermission = CategoryModel::checkPermission($categoryID, "Vanilla.Discussions.Add");
        } else {
            // Permission check for any category.
            $hasPermission = Gdn::session()->checkPermission("Vanilla.Discussions.Add", true, "Category", "any");
        }

        // If user isn't allowed or is a guest.
        if (!$hasPermission || !Gdn::session()->isValid()) {
            return [];
        }

        // Get allowed discussion types.
        if (PostTypeModel::isPostTypesFeatureEnabled()) {
            $allowedDiscussionTypes = CategoryModel::getAllowedPostTypeData(
                $categoryID === -1 ? null : $category,
                $groupID
            );
        } else {
            $allowedDiscussionTypes = $this->categoryModel::getAllowedDiscussionData($permissionCategory, $category);
        }

        $postableDiscussionTypes = PostTypeModel::isPostTypesFeatureEnabled()
            ? null
            : CategoryModel::instance()->getPostableDiscussionTypes();

        $this->props["items"] = [];
        foreach ($allowedDiscussionTypes as $discussionTypeKey => $discussionType) {
            // If the discussion type is explicitly excluded from this button's configuration.
            if (in_array(strtolower($discussionTypeKey), $excludedButtons)) {
                continue;
            }

            // Or we don't have global permission to add that type of discussion
            if (
                isset($discussionType["AddPermission"]) &&
                !Gdn::session()->checkPermission($discussionType["AddPermission"])
            ) {
                unset($allowedDiscussionTypes[$discussionTypeKey]);
                continue;
            }

            $url = $discussionType["AddUrl"];

            $categorySpecificPages = ["discussionCategoryPage", "discussion", "question", "idea", "event"];

            if (in_array($layoutViewType, $categorySpecificPages) && $categoryID !== -1) {
                $urlCode = rawurlencode($category["UrlCode"]);
                $url .= !str_contains($url, "?") ? "/" . $urlCode : "";
            }

            if (isset($groupID)) {
                $url .= UrlUtils::concatQuery($url, ["groupID" => $groupID]);
            }

            $this->props["items"][] = [
                "label" => $customLabels[strtolower($discussionTypeKey)] ?? ($discussionType["AddText"] ?? ""),
                "action" => $url,
                "type" => "link",
                "id" => str_replace(" ", "-", strtolower($discussionType["AddText"])),
                "icon" => $discussionType["AddIcon"],
                "asOwnButton" => in_array(strtolower($discussionTypeKey), $this->props["asOwnButtons"]),
            ];
        }

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
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function widgetSpecificSchema(): Schema
    {
        //prefilter allowed discussions for asOwnButton dropdown
        $permissionCategory = CategoryModel::permissionCategory(false);
        $globalAllowedDiscussions = CategoryModel::getAllowedDiscussionData($permissionCategory, []);

        if (PostTypeModel::isPostTypesFeatureEnabled()) {
            $postTypeModel = Gdn::getContainer()->get(PostTypeModel::class);
            $postTypes = $postTypeModel->getAvailablePostTypes();
            $asOwnButtonFormChoices = array_column($postTypes, "name", "postTypeID");
        } else {
            $asOwnButtonFormChoices = [];
            foreach (array_keys($globalAllowedDiscussions) as $allowedDiscussion) {
                $asOwnButtonFormChoices[strtolower($allowedDiscussion)] = $allowedDiscussion;
            }
        }

        $schema = Schema::parse([
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
        ]);

        if (!PostTypeModel::isPostTypesFeatureEnabled()) {
            // Hiding for post types. The display name of post types can be changed in Post Types settings.
            $schema->merge(Schema::parse(["customLabels:?" => self::labelsSchema($globalAllowedDiscussions)]));
        }

        $schema->merge(
            Schema::parse([
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
            ])
        );
        return $schema;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(self::widgetTitleSchema(), self::widgetSpecificSchema());
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $links = array_map(function (array $item) {
            return [
                "url" => $item["action"],
                "name" => $item["label"] ?? null,
            ];
        }, array_filter($props["items"] ?? []));
        $result = $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($links));
        return $result;
    }
}
