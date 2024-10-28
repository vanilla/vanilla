<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Permissions;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class DiscussionCommentsAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;
    use DiscussionCommentsAssetSchemaTrait;

    /** @var InternalClient */
    private InternalClient $internalClient;
    private ConfigurationInterface $configuration;
    private \Gdn_Session $session;

    /**
     * @param InternalClient $internalClient
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        InternalClient $internalClient,
        ConfigurationInterface $configuration,
        \Gdn_Session $session
    ) {
        $this->internalClient = $internalClient;
        $this->configuration = $configuration;
        $this->session = $session;
    }

    public function getProps(): ?array
    {
        $discussionID = $this->getHydrateParam("discussionID");
        $categoryID = $this->getHydrateParam("categoryID");
        $page = $this->getHydrateParam("page");
        $limit = $this->props["apiParams"]["limit"] ?? $this->configuration->get("Vanilla.Comments.PerPage");
        $collapseChildDepth = $this->props["apiParams"]["collapseChildDepth"] ?? null;

        $apiParams = [
            "discussionID" => $discussionID,
            "page" => $page,
            "limit" => $limit,
            "expand" => ["insertUser", "reactions", "attachments", "reportMeta"],
            "sort" => $this->getHydrateParam("sort") ?? ($this->props["apiParams"]["sort"] ?? "dateInserted"),
            "defaultSort" => $this->props["apiParams"]["sort"],
        ];

        $props = [];
        $threadStyle = \Gdn::config("threadStyle", "flat");
        // maybe we should always get comments and commentsThread ?
        if ($threadStyle === "nested") {
            $apiParams = $apiParams + [
                "parentRecordID" => $discussionID,
                "parentRecordType" => "discussion",
                "collapseChildDepth" => $collapseChildDepth,
            ];
            $commentsThread = $this->internalClient->get("/comments/thread", $apiParams)->asData();
            $props["commentsThread"] = $commentsThread->withPaging();

            // this one for seo html
            $comments = $commentsThread->getData();
            $props["comments"]["data"] = [];
            if ($comments && $comments["commentsByID"]) {
                $props["comments"]["data"] = array_values($comments["commentsByID"]);
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
                ->get("/drafts?recordType=comment&parentRecordID=" . $discussionID)
                ->asData();
            // If there is a drafts, pass it on
            if ($drafts[0]) {
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
        return "DiscussionCommentsAsset";
    }

    /**
     * TODO: Get new icon from design and replace
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/guest-cta.svg";
    }

    public static function getWidgetSchema(): Schema
    {
        return self::getAssetSchema();
    }

    public static function getWidgetName(): string
    {
        return "Comments";
    }

    public static function getWidgetID(): string
    {
        return "asset.discussionComments";
    }
}
