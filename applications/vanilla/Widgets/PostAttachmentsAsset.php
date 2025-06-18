<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class PostAttachmentsAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use HomeWidgetContainerSchemaTrait;

    /** @var InternalClient */
    private InternalClient $internalClient;

    /**
     * @param InternalClient $internalClient
     */
    public function __construct(InternalClient $internalClient)
    {
        $this->internalClient = $internalClient;
    }
    public function getProps(): ?array
    {
        $props = [
            "discussion" => $this->getHydrateParam("discussion"),
            "discussionApiParams" => $this->getHydrateParam("discussionApiParams"),
        ];
        return $props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Community";
    }

    public function renderSeoHtml(array $props): ?string
    {
        return "";
    }

    public static function getComponentName(): string
    {
        return "PostAttachmentsAsset";
    }

    public static function getWidgetIconPath(): ?string
    {
        return "";
    }

    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(allowDynamic: false),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(allowDynamic: false),
            self::containerOptionsSchema("containerOptions", minimalProperties: true, visualBackgroundType: "outer")
        );
    }

    public static function getWidgetName(): string
    {
        return "Post Attachments";
    }

    public static function getWidgetID(): string
    {
        return "asset.postAttachments";
    }
}
