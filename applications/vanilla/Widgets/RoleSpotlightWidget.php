<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Http\InternalClient;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\ReactWidget;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forms\FieldMatchConditional;

/**
 * Widget to spotlight a role.
 */
class RoleSpotlightWidget extends ReactWidget
{
    use HomeWidgetContainerSchemaTrait;

    public function __construct(private \Gdn_Session $session, private InternalClient $internalClient)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Role Spotlight";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "roleSpotlight";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "RoleSpotlightWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetGroup(): string
    {
        return "Members";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/roleSpotlight.svg";
    }

    /**
     * Get the schema for API params.
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema
    {
        return Schema::parse([
            "apiParams" => Schema::parse([
                "roleID" => [
                    "type" => "integer",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions(t("Role to Spotlight"), t("Choose a role."), t("Search...")),
                        new ApiFormChoices(
                            "/api/v2/roles?personalInfo=false&name=%s*",
                            "/api/v2/roles/%s",
                            "roleID",
                            "name"
                        )
                    ),
                ],
                "includeComments?" => [
                    "type" => "boolean",
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(t("Include Comments"), t("Include comments in the spotlight."))
                    ),
                ],

                /**
                 * Since we can't dynamically set the options for the sort field based on the includeComments prop,
                 * we use two different fields -- `sortIncludingComments` and `sortExcludingComments` -- each with a FieldMatchConditional.
                 */

                "sortExcludingComments?" => [
                    "type" => "string",
                    "default" => "-dateLastComment",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions(t("Sort Order")),
                        new StaticFormChoices([
                            "-dateLastComment" => t("Recently Commented"),
                            "-dateInserted" => t("Recently Created"),
                            "-score" => t("Top"),
                            "dateInserted" => t("Oldest"),
                        ]),
                        new FieldMatchConditional(
                            "apiParams.includeComments",
                            Schema::parse([
                                "type" => "boolean",
                                "const" => false,
                            ])
                        )
                    ),
                ],
                "sortIncludingComments?" => [
                    "type" => "string",
                    "default" => "-commentDate",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions(t("Sort Order")),
                        new StaticFormChoices([
                            "-commentDate" => t("By Comment Date"),
                            "-score" => t("Top"),
                        ]),
                        new FieldMatchConditional(
                            "apiParams.includeComments",
                            Schema::parse([
                                "type" => "boolean",
                                "const" => true,
                            ])
                        )
                    ),
                ],

                "limit" => [
                    "type" => "integer",
                    "description" => t("Desired number of items."),
                    "minimum" => 1,
                    "maximum" => 500,
                    "step" => 1,
                    "default" => 10,
                    "x-control" => SchemaForm::textBox(
                        new FormOptions(
                            t("Limit"),
                            t("Choose how many records to display."),
                            "",
                            t("Up to a maximum of 500 items may be displayed.")
                        ),
                        "number"
                    ),
                ],
                "showLoadMore?" => [
                    "type" => "boolean",
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(
                            t('Show "Load More"'),
                            "",
                            "",
                            t("Allow users to click to see additional items.")
                        )
                    ),
                ],
            ])
                ->setField("x-control", SchemaForm::section(new FormOptions("API Parameters")))
                ->setDescription("Configure how the data is fetched."),
        ]);
    }

    /**
     * Get the schema for item options.
     *
     * @return Schema
     */
    public static function getItemOptionsSchema(): Schema
    {
        return Schema::parse([
            "itemOptions" => Schema::parse([
                "excerpt:b?" => [
                    "description" => "Display excerpt",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(new FormOptions("Excerpt")),
                ],
                "category:b?" => [
                    "description" => "Display category",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(new FormOptions("Category")),
                ],
                "author:b?" => [
                    "description" => "Display author",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(new FormOptions("Author")),
                ],
                "dateUpdated:b?" => [
                    "description" => "Display lst updated date",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(new FormOptions("Date")),
                ],
                "userTags:b?" => [
                    "description" => "Display user tags",
                    "default" => true,
                    "x-control" => SchemaForm::checkBox(new FormOptions("User Tags")),
                ],
            ])
                ->setDescription("Configure the item options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Item Options"))),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(required: false),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(required: false),
            self::getApiSchema(),
            self::displayOptionsSchema(),
            self::getItemOptionsSchema(),
            self::containerOptionsSchema("containerOptions", viewAll: false, minimalProperties: true)
        );

        return $schema;
    }

    public function getRealApiParams(): array
    {
        $props = $this->props;
        $includeComments = $props["apiParams"]["includeComments"] ?? false;
        $sort = $props["apiParams"][$includeComments ? "sortIncludingComments" : "sortExcludingComments"];
        return [
            "roleIDs" => $props["apiParams"]["roleID"] ?? null,
            "includeComments" => $includeComments,
            "sort" => $sort,
            "limit" => $props["apiParams"]["limit"],
            "page" => 1,
        ];
    }

    /**
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array
    {
        $apiParams = $this->getRealApiParams();
        $props = $this->props;
        try {
            $paginatedPosts = $this->internalClient
                ->get("/posts", $apiParams)
                ->asData()
                ->withPaging();
        } catch (\Exception $e) {
            return null;
        }
        return [
            "title" => $props["title"],
            "subtitle" => $props["subtitle"],
            "description" => $props["description"],
            "posts" => $paginatedPosts,
            "postsApiParams" => $apiParams,
            "itemOptions" => $props["itemOptions"],
            "containerOptions" => $props["containerOptions"],
            "displayOptions" => $props["displayOptions"],
            "showLoadMore" => !!$props["apiParams"]["showLoadMore"],
        ];
    }

    public function renderSeoHtml(array $props): ?string
    {
        $twigProps = [
            "title" => $props["title"] ?? null,
            "description" => $props["description"] ?? null,
            "subtitle" => $props["subtitle"] ?? null,
        ];
        $postsData = $props["posts"]["data"] ?? [];
        return $this->renderWidgetContainerSeoContent($twigProps, $this->renderSeoLinkList($postsData));
    }
}
