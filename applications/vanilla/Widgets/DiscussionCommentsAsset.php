<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class DiscussionCommentsAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;

    /** @var InternalClient */
    private InternalClient $internalClient;
    private ConfigurationInterface $configuration;

    /**
     * @param InternalClient $internalClient
     * @param ConfigurationInterface $configuration
     */
    public function __construct(InternalClient $internalClient, ConfigurationInterface $configuration)
    {
        $this->internalClient = $internalClient;
        $this->configuration = $configuration;
    }

    public function getProps(): ?array
    {
        $discussionID = $this->getHydrateParam("discussionID");
        $page = $this->getHydrateParam("page");
        $limit = $this->props["apiParams"]["limit"] ?? $this->configuration->get("Vanilla.Comments.PerPage");
        $apiParams = [
            "discussionID" => $discussionID,
            "page" => $page,
            "limit" => $limit,
            "expand" => ["insertUser"],
        ];
        $comments = $this->internalClient->get("/comments", $apiParams)->asData();
        $props =
            [
                "commentsPreload" => $comments->withPaging(),
                "apiParams" => array_merge($this->props["apiParams"] ?? [], $apiParams),
                "discussionID" => $this->getHydrateParam("discussionID"),
                "discussion" => $this->getHydrateParam("discussion"),
            ] + $this->props;

        return $props;
    }

    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderTwigFromString(
            <<<TWIG
<h2>{{t("Comments")}}</h2>
{% for comment in commentsPreload.data %}
<div class="comment separated">
<div>{{ renderSeoUser(comment.insertUser) }}</div>
<div class="userContent">{{ comment.body|raw }}</div>
</div>
{% endfor %}
{% if empty(commentsPreload.data) %}
{{ t("There are no comments yet") }}
{% endif %}
TWIG
            ,
            $props
        );
        return $result;
    }

    public static function getComponentName(): string
    {
        return "DiscussionCommentsAsset";
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
        $schema = SchemaUtils::composeSchemas(
            Schema::parse([
                "apiParams" => [
                    "default" => [],
                    "type" => "object",
                    "properties" => [
                        "limit" => [
                            "type" => "integer",
                            "x-control" => SchemaForm::textBox(new FormOptions("Count Comments"), "number"),
                        ],
                    ],
                ],
            ])
        );
        $schema
            ->setDescription("Configure API options")
            ->setField("x-control", SchemaForm::section(new FormOptions("Data Options")));

        return $schema;
    }

    public static function getWidgetName(): string
    {
        return "Comments";
    }

    public static function getWidgetID(): string
    {
        return "asset.discussionComments";
    }
}
