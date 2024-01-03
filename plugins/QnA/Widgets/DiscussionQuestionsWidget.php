<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Widgets;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use QnaModel;
use Vanilla\Forum\Modules\QnAWidgetModule;
use Vanilla\Forum\Widgets\DiscussionsWidgetSchemaTrait;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\FilterableWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class DiscussionQuestionsWidget
 */
class DiscussionQuestionsWidget extends QnAWidgetModule implements ReactWidgetInterface
{
    use DiscussionsWidgetSchemaTrait;
    use FilterableWidgetTrait;

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string
    {
        return "discussion.questions";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string
    {
        return "Questions";
    }

    /**
     * @inheridoc
     */
    public static function getComponentName(): string
    {
        return "DiscussionsWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/questions.svg";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(parent::getWidgetSchema());
        return $schema;
    }

    /**
     * @inheritDoc
     */
    public static function getApiSchema(): Schema
    {
        $filterTypeSchemaExtraOptions = parent::getFilterTypeSchemaExtraOptions();

        $apiSchema = parent::getBaseApiSchema();
        $apiSchema->merge(
            SchemaUtils::composeSchemas(
                self::qnaFilterSchema(),
                self::filterTypeSchema(
                    ["subcommunity", "category", "none"],
                    ["subcommunity", "none"],
                    $filterTypeSchemaExtraOptions
                ),
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
     * @throws ValidationException
     */
    protected function getRealApiParams(?array $params = null): array
    {
        $apiParams = parent::getWidgetRealApiParams();
        $apiParams["type"] = QnaModel::TYPE;
        $status = $apiParams["status"] ?? "";
        if ($status === self::ALL_QUESTIONS) {
            unset($apiParams["status"]);
        }

        return $apiParams;
    }
}
