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

/**
 * Base editor comment editor asset.
 */
abstract class AbstractCreateCommentAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;

    /**
     * DI
     */
    public function __construct(
        protected \Gdn_Session $session,
        protected InternalClient $internalClient,
        protected \CommentModel $commentModel
    ) {
    }

    abstract protected function getParentRecordType(): string;
    abstract protected function getParentRecordID(): string;

    /**
     * @return bool
     */
    protected function canComment(): bool
    {
        return $this->commentModel
            ->getParentHandler($this->getParentRecordType())
            ->hasAddPermission($this->getParentRecordID(), throw: false);
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        $categoryID = $this->getHydrateParam("categoryID");

        if (!$this->canComment()) {
            return null;
        }

        $props = [
            "parentRecordType" => $this->getParentRecordType(),
            "parentRecordID" => $this->getParentRecordID(),
            "categoryID" => $categoryID,
        ];

        $drafts = $this->internalClient
            ->get("/drafts", [
                "parentRecordType" => $this->getParentRecordType(),
                "parentRecordID" => $this->getParentRecordID(),
                "recordType" => "comment",
            ])
            ->asData();

        // If there is a drafts, pass it on
        $initialDraft = $drafts[0] ?? null;
        if ($initialDraft != null) {
            $props["initialDraft"] = $initialDraft;
        }

        $props = array_merge($props, $this->props);

        return $props;
    }

    /**
     * @param array $props
     * @return string|null
     */
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

    /**
     * @return string
     */
    public static function getComponentName(): string
    {
        return "CreateCommentAsset";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/AddComment.svg";
    }

    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(defaultValue: "Leave a comment", allowDynamic: false),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(allowDynamic: false),
            self::containerOptionsSchema("containerOptions", minimalProperties: true, visualBackgroundType: "outer")
        );
    }

    public static function getWidgetName(): string
    {
        return "Comment Editor";
    }

    public static function getWidgetID(): string
    {
        return "asset.createComment";
    }
}
