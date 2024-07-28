<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
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

/**
 * Asset to display the Suggested Answers on a layout
 */
class DiscussionSuggestionsAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;

    /** @var InternalClient */
    private InternalClient $internalClient;

    /**
     * DI.
     *
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
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "DiscussionSuggestionsAsset";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([]);
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Discussion Suggestions";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "asset.discussionSuggestions";
    }
}
