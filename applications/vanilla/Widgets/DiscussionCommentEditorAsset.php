<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
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

    /**
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session)
    {
        $this->session = $session;
    }

    public function getProps(): ?array
    {
        $categoryID = $this->getHydrateParam("categoryID");
        $hasPermission = $this->session
            ->getPermissions()
            ->has(
                "comments.add",
                $categoryID,
                Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                \CategoryModel::PERM_JUNCTION_TABLE
            );

        if (!$hasPermission) {
            return null;
        }
        $props =
            [
                "discussionID" => $this->getHydrateParam("discussionID"),
                "categoryID" => $categoryID,
            ] + $this->props;
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
        return "/applications/dashboard/design/images/widgetIcons/guest-cta.svg";
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
