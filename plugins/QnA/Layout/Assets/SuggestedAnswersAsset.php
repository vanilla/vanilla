<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Layout\Assets;

use Garden\Schema\Schema;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Web\TwigRenderTrait;

/**
 * Asset to display the Suggested Answers on a layout
 */
class SuggestedAnswersAsset extends AbstractLayoutAsset implements HydrateAwareInterface
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
    public function __construct(InternalClient $internalClient, private AiSuggestionSourceService $aiSuggestions)
    {
        $this->internalClient = $internalClient;
    }
    public function getProps(): ?array
    {
        if (!$this->aiSuggestions->suggestionEnabled()) {
            return null;
        }

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
        return "SuggestedAnswersAsset";
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
        return "Suggested Answers";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "asset.suggestedAnswers";
    }
}
