<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Permissions;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class DiscussionCommentEditorAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;

    private \Gdn_Session $session;
    private InternalClient $internalClient;

    /**
     * @param \Gdn_Session $session
     * @param InternalClient $internalClient
     */
    public function __construct(\Gdn_Session $session, InternalClient $internalClient)
    {
        $this->session = $session;
        $this->internalClient = $internalClient;
    }

    public function getProps(): ?array
    {
        $discussion = $this->getHydrateParam("discussion");
        $categoryID = $this->getHydrateParam("categoryID");
        $permissions = $this->session->getPermissions();

        $hasCommentsAddPermission = $permissions->has(
            "comments.add",
            $categoryID,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            \CategoryModel::PERM_JUNCTION_TABLE
        );

        if (!$hasCommentsAddPermission) {
            return null;
        }

        if ($discussion["closed"]) {
            $hasDiscussionsClosePermission = $permissions->has(
                "discussions.close",
                $categoryID,
                Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                \CategoryModel::PERM_JUNCTION_TABLE
            );

            if (!$hasDiscussionsClosePermission) {
                return null;
            }
        }

        $props = [
            "discussionID" => $this->getHydrateParam("discussionID"),
            "categoryID" => $categoryID,
        ];

        $drafts = $this->internalClient
            ->get("/drafts?recordType=comment&parentRecordID=" . $this->getHydrateParam("discussionID"))
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

        $props = array_merge($props, $this->props);

        return $props;
    }

    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderWidgetContainerSeoContent(
            $props,
            $this->renderTwigFromString(
                <<<TWIG
You need to Enable Javascript to Leave a Comment.
TWIG
                ,
                $props
            )
        );
        return $result;
    }

    public static function getComponentName(): string
    {
        return "DiscussionCommentEditorAsset";
    }

    /**
     * TODO: Get new icon from design and replace
     */
    public static function getWidgetIconPath(): ?string
    {
        return "";
    }

    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema("Leave a Comment"),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema()
        );
    }

    public static function getWidgetName(): string
    {
        return "Comment Editor";
    }

    public static function getWidgetID(): string
    {
        return "asset.comment-editor";
    }
}
