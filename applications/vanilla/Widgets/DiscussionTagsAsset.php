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
        $tags = $this->getHydrateParam("tags") ?? [];
        $props =
            [
                "tags" => $tags,
            ] + $this->props;
        return $props;
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

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/Tag.svg";
    }

    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(defaultValue: "Find more posts tagged with", allowDynamic: false),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(allowDynamic: false),
            self::containerOptionsSchema("containerOptions", minimalProperties: true, visualBackgroundType: "outer")
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
