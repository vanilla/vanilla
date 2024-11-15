<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Layout\Assets;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\DiscussionCommentsAsset;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\FormOptions;

class TabbedCommentListAsset extends DiscussionCommentsAsset
{
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
        $props = parent::getProps();
        $apiParams = $props["apiParams"];
        $permissions = $this->session->getPermissions();

        $acceptedAnswersApiParams = array_merge($apiParams, ["qna" => "accepted", "limit" => 500, "page" => 1]);
        $acceptedAnswers = $this->internalClient->get("/comments", $acceptedAnswersApiParams)->asData();

        $props =
            [
                "acceptedAnswersApiParams" => $acceptedAnswersApiParams,
                "acceptedAnswers" => $acceptedAnswers->withPaging(),
            ] + $props;

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

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/AnswerListTabs.svg";
    }

    public static function getWidgetName(): string
    {
        return "Q&A Comments";
    }

    public static function getWidgetID(): string
    {
        return "asset.tabbed-comment-list";
    }

    /**
     * @inheritDoc
     */
    protected static function getHeaderSchema(): Schema
    {
        return Schema::parse([
            "tabTitles" => [
                "type" => "object",
                "required" => ["all", "accepted", "rejected"],
                "properties" => [
                    "all" => [
                        "type" => "string",
                        "default" => "All Comments",
                        "x-control" => SchemaForm::textBox(new FormOptions("All Comments Title")),
                    ],
                    "accepted" => [
                        "type" => "string",
                        "default" => "Accepted Answers",
                        "x-control" => SchemaForm::textBox(new FormOptions("Accepted Answers Title")),
                    ],
                    "rejected" => [
                        "type" => "string",
                        "default" => "Rejected Answers",
                        "x-control" => SchemaForm::textBox(new FormOptions("Rejected Answers Title")),
                    ],
                ],
            ],
        ]);
    }
}
