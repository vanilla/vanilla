<?php
/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
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

class DiscussionTagsAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
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
        $discussionID = $this->getHydrateParam("discussionID");
        $tags = $this->getHydrateParam("discussion.tags");
        if ($discussionID) {
            $props =
                [
                    "tags" => $tags,
                ] + $this->props;
            return $props;
        }
        return $this->props;
    }

    public function renderSeoHtml(array $props): ?string
    {
        // Make safe URLS for each tag
        $tags = $props["tags"];
        foreach ($tags as &$tag) {
            $tag["name"] = $tag["name"];
            $tag["url"] = url("/discussions?tagID=" . $tag["tagID"]);
        }

        // Create the HTML for it
        $tagString = $this->renderTwigFromString(
            <<<TWIG
{% for tag in tags %}
    <li>
        <a href="{{ tag.url }}">{{ tag.name }}</a>
    </li>
{% endfor %}
TWIG
            ,
            ["tags" => $tags]
        );

        // Render in the home widget container
        return $this->renderWidgetContainerSeoContent($props, $tagString);
    }

    public static function getComponentName(): string
    {
        return "DiscussionTagAsset";
    }

    public static function getWidgetIconPath(): ?string
    {
        return "";
    }

    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema("Type your title here", false, "Find more posts tagged with"),
            self::widgetSubtitleSchema("Type your subtitle here"),
            self::widgetDescriptionSchema(),
            self::containerOptionsSchema("containerOptions", null, true)
        );

        return $schema;
    }

    public static function getWidgetName(): string
    {
        return "Tags";
    }

    public static function getWidgetID(): string
    {
        return "asset.discussionTagsAsset";
    }
}
