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
use Vanilla\Web\TwigRenderTrait;

class DiscussionOriginalPostAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;

    public function getProps(): ?array
    {
        $props =
            [
                "discussionID" => $this->getHydrateParam("discussionID"),
                "categoryID" => $this->getHydrateParam("categoryID"),
                "discussion" => $this->getHydrateParam("discussion"),
                "category" => $this->getHydrateParam("category"),
                "page" => $this->getHydrateParam("page"),
            ] + $this->props;
        return $props;
    }

    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderTwigFromString(
            <<<TWIG
<h1>{{discussion.name}}</h1>
<div>{{ renderSeoUser(discussion.insertUser) }}</div>
<div class="userContent">
{{discussion.body|raw}}
</div>
TWIG
            ,
            $props
        );
        return $result;
    }

    public static function getComponentName(): string
    {
        return "DiscussionOriginalPostAsset";
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
        return Schema::parse([]);
    }

    public static function getWidgetName(): string
    {
        return "Discussion";
    }

    public static function getWidgetID(): string
    {
        return "asset.discussionOriginalPostAsset";
    }
}
