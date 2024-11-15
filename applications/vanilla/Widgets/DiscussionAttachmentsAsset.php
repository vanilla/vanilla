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
use Vanilla\Web\TwigRenderTrait;

class DiscussionAttachmentsAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;

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

    public function renderSeoHtml(array $props): ?string
    {
        return "";
    }

    public static function getComponentName(): string
    {
        return "DiscussionAttachmentsAsset";
    }

    public static function getWidgetIconPath(): ?string
    {
        return "";
    }

    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([]);
    }

    public static function getWidgetName(): string
    {
        return "Discussion Attachments";
    }

    public static function getWidgetID(): string
    {
        return "asset.discussionAttachments";
    }
}
