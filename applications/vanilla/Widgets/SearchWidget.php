<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\Fragments\SearchFragmentMeta;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\ReactWidget;
use Vanilla\Widgets\WidgetSchemaTrait;

/**
 * Class SearchWidget
 */
class SearchWidget extends ReactWidget
{
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;

    /** @var AddonManager */
    private $addonManager;

    /**
     * SearchWidget constructor.
     *
     * @param AddonManager $addonManager The addon manager.
     */
    public function __construct(AddonManager $addonManager)
    {
        $this->addonManager = $addonManager;
    }

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [SearchFragmentMeta::class];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "community-search";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Search";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SearchWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/SearchWidget.svg";
    }

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array
    {
        // Ensure we use singular 'event' on the search page params, in case we have saved a widget with 'events' as the domain.
        $props = $this->props;
        if (isset($props["domain"]) && $props["domain"] === "events") {
            $props["domain"] = "event";
        }
        return $props;
    }

    public function getEnabledScopes(): array
    {
        $scope = [
            "all" => "All",
            "discussions" => "Posts",
            "places" => "Places",
            "members" => "Members",
        ];

        if ($this->addonManager->isEnabled("knowledge", Addon::TYPE_ADDON)) {
            $scope["knowledge"] = "Articles";
        }

        if ($this->addonManager->isEnabled("groups", Addon::TYPE_ADDON)) {
            $scope["event"] = "Events";
        }

        return $scope;
    }

    public function getEnabledPlaces(): array
    {
        $places = [
            "category" => "Categories",
        ];

        if ($this->addonManager->isEnabled("groups", Addon::TYPE_ADDON)) {
            $places["group"] = "Groups";
        }

        if ($this->addonManager->isEnabled("knowledge", Addon::TYPE_ADDON)) {
            $places["knowledgeBase"] = "Knowledge Bases";
        }

        return $places;
    }

    public function getEnabledPostTypes(): array
    {
        $postTypeOptions = [];
        $postTypeModel = \Gdn::getContainer()->get(PostTypeModel::class);

        // Always include base post types, regardless of feature flag
        $baseTypes = $postTypeModel->getAvailableBasePostTypes();
        foreach ($baseTypes as $type) {
            $postType = $postTypeModel->getByID($type);
            if ($postType) {
                $postTypeOptions[$type] = $postType["name"];
            }
        }

        return $postTypeOptions;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $instance = \Gdn::getContainer()->get(self::class);

        $searchSchema = Schema::parse([
            "placeholder?" => [
                "type" => "string",
                "description" => "The placeholder text appearing in an empty search box.",
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Placeholder", "The placeholder text appearing in an empty search box."),
                    "text"
                ),
            ],
            "borderRadius?" => [
                "type" => "string",
                "description" => "The border radius of the search box.",
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Border Radius", "The border radius of the search box."),
                    "number"
                ),
            ],
            "domain" => [
                "type" => "string",
                "description" => "Filter the search by type.",
                "default" => "all",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Filter By", "Filter the search by type."),
                    new StaticFormChoices($instance->getEnabledScopes())
                ),
            ],
            "places?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                ],
                "description" => "Which places should be included in the search.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Places",
                        "Which places should be included in the search.",
                        "All Searchable Places"
                    ),
                    new StaticFormChoices($instance->getEnabledPlaces()),
                    new FieldMatchConditional(
                        "domain",
                        Schema::parse([
                            "type" => "string",
                            "enum" => ["places"],
                        ])
                    ),
                    true
                ),
            ],
            "postType?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                ],
                "description" => "Filter the search by the type of post.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Post Types",
                        "Filter the search by the type of post.",
                        "All Searchable Post Types"
                    ),
                    new StaticFormChoices($instance->getEnabledPostTypes()),
                    new FieldMatchConditional(
                        "domain",
                        Schema::parse([
                            "type" => "string",
                            "enum" => ["discussions"],
                        ])
                    ),
                    true
                ),
            ],
        ]);

        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(null, false, "Search Community"),
            self::widgetDescriptionSchema(null, false, "Search this community for posts, articles and more."),
            $searchSchema
        );
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        return $this->renderWidgetContainerSeoContent(
            $props,
            $this->renderTwigFromString(
                <<<TWIG
You need to Enable Javascript to search this community.
TWIG
                ,
                $props
            )
        );
    }
}
