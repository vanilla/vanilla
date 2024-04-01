<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Layout\Assets;

use Garden\Schema\Schema;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\FormOptions;

class TabbedCommentListAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HomeWidgetContainerSchemaTrait;
    use HydrateAwareTrait;
    use TwigRenderTrait;

    private \Gdn_Session $session;
    /** @var InternalClient */
    private InternalClient $internalClient;
    private ConfigurationInterface $configuration;
    public function __construct(
        \Gdn_Session $session,
        InternalClient $internalClient,
        ConfigurationInterface $configuration
    ) {
        $this->session = $session;
        $this->internalClient = $internalClient;
        $this->configuration = $configuration;
    }

    public function renderSeoHtml(array $props): ?string
    {
        return $this->renderWidgetContainerSeoContent(
            $props,
            $this->renderTwigFromString(
                <<<TWIG
{% if acceptedAnswers|default(null) %}
    <h2>{{t("Accepted answers")}}</h2>
    {% for comment in acceptedAnswers.data %}
        <div class="comment separated">
            <div>{{ renderSeoUser(comment.insertUser) }}</div>
            <div class="userContent">{{ comment.body|raw }}</div>
        </div>
    {% endfor %}
    {% if empty(acceptedAnswers.data) %}
        {{ t("There are no accepted answers yet") }}
    {% endif %}
{% endif %}
<h2>{{t("All comments")}}</h2>
{% for comment in comments.data %}
    <div class="comment separated">
        <div>{{ renderSeoUser(comment.insertUser) }}</div>
        <div class="userContent">{{ comment.body|raw }}</div>
    </div>
{% endfor %}
{% if empty(comments.data) %}
    {{ t("There are no accepted answers yet") }}
{% endif %}

{% if rejectedAnswers|default(null) %}
    <h2>{{t("Rejected answers")}}</h2>
    {% for comment in rejectedAnswers.data %}
        <div class="comment separated">
            <div>{{ renderSeoUser(comment.insertUser) }}</div>
            <div class="userContent">{{ comment.body|raw }}</div>
        </div>
    {% endfor %}
    {% if empty(rejectedAnswers.data) %}
        {{ t("There are no rejected answers yet") }}
    {% endif %}
{% endif %}
TWIG
                ,
                $props
            )
        );
    }

    public function getProps(): ?array
    {
        $permissions = $this->session->getPermissions();

        $discussionID = $this->getHydrateParam("discussionID");
        $page = $this->getHydrateParam("page");
        $limit = $this->props["apiParams"]["limit"] ?? $this->configuration->get("Vanilla.Comments.PerPage");
        $apiParams = [
            "discussionID" => $discussionID,
            "page" => $page,
            "limit" => $limit,
            "expand" => ["insertUser", "reactions"],
        ];
        $comments = $this->internalClient->get("/comments", $apiParams)->asData();

        $acceptedAnswersApiParams = array_merge($apiParams, ["qna" => "accepted", "limit" => 500, "page" => 1]);
        $acceptedAnswers = $this->internalClient->get("/comments", $acceptedAnswersApiParams)->asData();

        $props =
            [
                "discussion" => $this->getHydrateParam("discussion"),
                "discussionApiParams" => $this->getHydrateParam("discussionApiParams"),
                "comments" => $comments->withPaging(),
                "apiParams" => array_merge($this->props["apiParams"] ?? [], $apiParams),
                "acceptedAnswersApiParams" => $acceptedAnswersApiParams,
                "acceptedAnswers" => $acceptedAnswers->withPaging(),
            ] + $this->props;

        if ($permissions->has("Garden.Curation.Manage")) {
            $rejectedAnswersApiParams = array_merge($apiParams, ["qna" => "rejected", "limit" => 500, "page" => 1]);
            $rejectedAnswers = $this->internalClient
                ->get("/comments", array_merge($apiParams, $rejectedAnswersApiParams))
                ->asData();
            $props += [
                "rejectedAnswersApiParams" => $rejectedAnswersApiParams,
                "rejectedAnswers" => $rejectedAnswers->withPaging(),
            ];
        }

        return $props;
    }

    public static function getComponentName(): string
    {
        return "TabbedCommentListAsset";
    }

    public static function getWidgetIconPath(): ?string
    {
        return "";
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
        return "Tabbed Comment List";
    }

    public static function getWidgetID(): string
    {
        return "asset.tabbed-comment-list";
    }
}
