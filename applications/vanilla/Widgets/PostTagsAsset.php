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
use TagModel;

class PostTagsAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;

    /**
     * @param InternalClient $internalClient
     * @param TagModel $tagModel
     */
    public function __construct(protected InternalClient $internalClient, protected TagModel $tagModel)
    {
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
        // Make safe URLS for each tag
        $tags = $this->getHydrateParam("tags");
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
        return "PostTagsAsset";
    }

    /**
     * @inheritdoc
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

    /**
     * @return array|null
     */
    public function getProps(): ?array
    {
        $taggingEnabled = $this->tagModel->discussionTaggingEnabled();

        if (!$taggingEnabled) {
            return null;
        }

        $props = $this->props;

        $props["title"] = t($props["title"]);

        return $props;
    }

    public static function getWidgetName(): string
    {
        return "Post Tags";
    }

    public static function getWidgetID(): string
    {
        return "asset.postTags";
    }
}
