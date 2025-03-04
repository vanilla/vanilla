<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\FormPickerOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Permissions;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class PostCommentThreadAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;
    use PostCommentsAndOriginalPostTrait;

    public function __construct(
        protected InternalClient $internalClient,
        protected ConfigurationInterface $configuration,
        protected \Gdn_Session $session
    ) {
    }

    /**
     * @return array
     */
    protected function getParentRecordParams(): array
    {
        return [
            "parentRecordType" => "discussion",
            "parentRecordID" => $this->getHydrateParam("discussionID"),
        ];
    }

    public function getProps(): ?array
    {
        $categoryID = $this->getHydrateParam("categoryID");
        $page = $this->getHydrateParam("page");
        $commentID = $this->getHydrateParam("commentID");

        $limit = $this->props["apiParams"]["limit"] ?? $this->configuration->get("Vanilla.Comments.PerPage");
        $collapseChildDepth = $this->props["apiParams"]["collapseChildDepth"] ?? 3;

        $parentRecordParams = $this->getParentRecordParams();
        $apiParams = $parentRecordParams + [
            "page" => $page,
            "limit" => $limit,
            "expand" => ["insertUser", "updateUser", "reactions", "attachments", "reportMeta"],
            "sort" => $this->getHydrateParam("sort") ?: $this->props["apiParams"]["sort"] ?? "dateInserted",
            "defaultSort" => $this->props["apiParams"]["sort"],
        ];

        // include needed expands if corresponding addons are enabled
        $isBadgesEnabled = \Gdn::addonManager()->isEnabled("badges", \Vanilla\Addon::TYPE_ADDON);
        $isWarningsEnabled = \Gdn::addonManager()->isEnabled("warnings2", \Vanilla\Addon::TYPE_ADDON);
        $isSignaturesEnabled = \Gdn::addonManager()->isEnabled("Signatures", \Vanilla\Addon::TYPE_ADDON);
        if ($isBadgesEnabled) {
            $apiParams["expand"][] = "insertUser.badges";
        }
        if ($isWarningsEnabled) {
            $apiParams["expand"][] = "warnings";
        }
        if ($isSignaturesEnabled) {
            $apiParams["expand"][] = "insertUser.signature";
        }

        $props = [];
        $maxDepth = (int) $this->props["apiParams"]["maxDepth"];
        $threadStyle = $maxDepth == 1 ? "flat" : "nested";
        $props["threadStyle"] = $threadStyle;
        // maybe we should always get comments and commentsThread ?
        if ($threadStyle === "nested") {
            // check if $collapseChildDepth is valid value, if not fallback to null which will be the default eventually
            $collapseChildDepth = ((int) $collapseChildDepth) <= $maxDepth ? $maxDepth : $collapseChildDepth;
            $apiParams = $apiParams + [
                "collapseChildDepth" => $collapseChildDepth,
            ];
            if ($commentID) {
                $apiParams["focusCommentID"] = $commentID;
            }

            $commentsThread = $this->internalClient->get("/comments/thread", $apiParams)->asData();
            $props["commentsThread"] = $commentsThread->withPaging();

            // this one for seo html
            $comments = $commentsThread->getData();
            $props["comments"]["data"] = [];
            if ($comments && $comments["commentsByID"]) {
                $props["comments"]["data"] = array_values($comments["commentsByID"]);
                $props["comments"]["paging"] = [];
            }
        } else {
            $comments = $this->internalClient->get("/comments", $apiParams)->asData();
            $props["comments"] = $comments->withPaging();
        }

        $props =
            $props + [
                "apiParams" => array_merge($this->props["apiParams"] ?? [], $apiParams),
                "discussion" => $this->getHydrateParam("discussion"),
                "discussionApiParams" => $this->getHydrateParam("discussionApiParams"),
            ] +
            $this->props;

        $permissions = $this->session->getPermissions();

        $hasCommentsAddPermission = $permissions->has(
            "comments.add",
            $categoryID,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            \CategoryModel::PERM_JUNCTION_TABLE
        );

        if ($hasCommentsAddPermission) {
            $drafts = $this->internalClient
                ->get(
                    "/drafts",
                    $parentRecordParams + [
                        "recordType" => "comment",
                    ]
                )
                ->asData();
            // If there is a drafts, pass it on
            if ($drafts[0] ?? null) {
                $draft = [
                    "draft" => [
                        "draftID" => $drafts[0]["draftID"],
                        "body" => $drafts[0]["attributes"]["body"],
                        "dateUpdated" => $drafts[0]["dateUpdated"],
                        "format" => $drafts[0]["attributes"]["format"],
                    ],
                ];
                $props = array_merge($props, $draft);
            }
        }

        return $props;
    }

    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderTwigFromString(
            <<<TWIG
<h2>{{t("Comments")}}</h2>
{% for comment in comments.data %}
<div class="comment separated">
<div>{{ renderSeoUser(comment.insertUser) }}</div>
<div class="userContent">{{ comment.body|raw }}</div>
</div>
{% endfor %}
{% if empty(comments.data) %}
{{ t("There are no comments yet") }}
{% endif %}
TWIG
            ,
            $props
        );
        return $result;
    }

    public static function getComponentName(): string
    {
        return "CommentThreadAsset";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/CommentList.svg";
    }

    public static function getWidgetSchema(): Schema
    {
        return self::getAssetSchema();
    }

    public static function getWidgetName(): string
    {
        return "Comment Thread";
    }

    public static function getWidgetID(): string
    {
        return "asset.postCommentThread";
    }

    /**
     * Get the schema.
     *
     * @return Schema
     */
    public static function getAssetSchema(): Schema
    {
        $apiSchema = [
            "default" => [],
            "type" => "object",
            "description" => "Configure Data",
            "properties" => [
                "sort" => [
                    "type" => ["string", "null"],
                    "default" => "dateInserted",
                    "enum" => ["-dateInserted", "dateInserted", "-score", "-" . ModelUtils::SORT_TRENDING],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions(t("Default Sort Order"), t("Choose the order records are sorted by default.")),
                        new StaticFormChoices([
                            "dateInserted" => t("Oldest"),
                            "-dateInserted" => t("Newest"),
                            "-score" => t("Top"),
                            "-experimentalTrending" => t("Trending"),
                        ])
                    ),
                ],
                "maxDepth" => [
                    "type" => "integer",
                    "maximum" => 5,
                    "default" => "5",
                    "x-control" => SchemaForm::radioPicker(
                        new FormOptions("Nesting Depth"),
                        pickerOptions: FormPickerOptions::create()
                            ->option(
                                "Flat",
                                "1",
                                "When selected, no nesting will occur and previously nested comments will quote their parent comment."
                            )
                            ->option("2 Levels", "2", "Comments may be nested up to 2 levels deep.")
                            ->option("3 Levels", "3", "Comments may be nested up to 3 levels deep.")
                            ->option("4 Levels", "4", "Comments may be nested up to 4 levels deep.")
                            ->option("5 Levels", "5", "Comments may be nested up to 5 levels deep.")
                    ),
                ],
                "collapseChildDepth" => [
                    "type" => "integer",
                    "default" => "3",
                    "x-control" => SchemaForm::radioPicker(
                        new FormOptions(
                            "Collapse Level",
                            "",
                            "",
                            "Comments at the set level and beyond will load onto the page as collapsed, and the user may expand to view."
                        ),
                        pickerOptions: FormPickerOptions::create()
                            ->option("2nd Level", "2")
                            ->option("3rd Level", "3")
                            ->option("4th Level", "4")
                            ->option("5th Level", "5"),
                        conditional: new FieldMatchConditional(
                            "apiParams.maxDepth",
                            new Schema([
                                "enum" => ["2", "3", "4", "5"],
                            ])
                        )
                    ),
                ],
            ],
        ];

        $schema = SchemaUtils::composeSchemas(
            static::getHeaderSchema(),
            Schema::parse([
                "apiParams?" => $apiSchema,
                "showOPTag?" => [
                    "type" => "boolean",
                    "default" => true,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(
                            t("Show OP Indicator"),
                            "",
                            "",
                            t("If this option is enabled, replies from the Original Poster will have an OP indicator.")
                        )
                    ),
                ],
            ]),
            self::authorBadgesSchema(),
            self::containerOptionsSchema(
                "containerOptions",
                minimalProperties: true,
                visualBackgroundType: "inner",
                defaultBorderType: "separator"
            )
        )->setField("properties.containerOptions.properties.headerAlignment.x-control", null);
        return $schema;
    }

    /**
     * Get schemas for the heading items.
     *
     * @return Schema
     */
    protected static function getHeaderSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(defaultValue: "Comments", allowDynamic: false),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(allowDynamic: false)
        );
    }
}
