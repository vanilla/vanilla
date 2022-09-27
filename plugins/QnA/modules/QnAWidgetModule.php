<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\Schema\Schema;
use QnaModel;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;

/**
 * Class QnAnWidgetModule
 *
 * @deprecated Use DiscussionQuestionsWidget instead.
 * @package Vanilla\Forum\Modules
 */
class QnAWidgetModule extends BaseDiscussionWidgetModule
{
    const ALL_QUESTIONS = "all";

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "List - Questions";
    }

    /**
     * @inheritDoc
     */
    public static function getApiSchema(): Schema
    {
        $apiSchema = parent::getApiSchema();
        $apiSchema->merge(
            SchemaUtils::composeSchemas(
                self::qnaFilterSchema(),
                self::categorySchema(),
                self::siteSectionIDSchema(),
                self::sortSchema(),
                self::limitSchema()
            )
        );

        return $apiSchema;
    }

    /**
     * Get the real parameters that we will pass to the API.
     * @param array|null $params
     * @return array
     */
    protected function getRealApiParams(?array $params = null): array
    {
        $apiParams = parent::getRealApiParams();
        $apiParams["type"] = QnaModel::TYPE;
        $status = $apiParams["status"] ?? "";
        if ($status === self::ALL_QUESTIONS) {
            unset($apiParams["status"]);
        }

        return $apiParams;
    }

    /**
     * QnA schema filter.
     *
     * @return Schema
     */
    protected static function qnaFilterSchema(): Schema
    {
        return Schema::parse([
            "status:s?" => [
                "enum" => array_map("strtolower", [
                    QnaModel::ACCEPTED,
                    QnaModel::ANSWERED,
                    QnaModel::UNANSWERED,
                    self::ALL_QUESTIONS,
                ]),
                "default" => self::ALL_QUESTIONS,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(t("Status"), t("Filter by question status")),
                    new StaticFormChoices([
                        strtolower(QnaModel::ACCEPTED) => t("Accepted answer only"),
                        strtolower(QnaModel::ANSWERED) => t("Answered questions only"),
                        strtolower(QnaModel::UNANSWERED) => t("Unanswered questions only"),
                        strtolower(self::ALL_QUESTIONS) => t("All"),
                    ])
                ),
            ],
        ]);
    }
}
