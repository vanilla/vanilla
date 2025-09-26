<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\InjectableInterface;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\ReactWidget;

/**
 * Widget to spotlight a user.
 */
class UserSpotlightWidget extends ReactWidget implements InjectableInterface
{
    use HomeWidgetContainerSchemaTrait;
    use UserSpotlightWidgetTrait;

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "User Spotlight";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "userspotlight";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "UserSpotlightWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetGroup(): string
    {
        return "Members";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/userspotlight.svg";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(null, true, "Customer Spotlight"),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(
                null,
                true,
                "Use this space to add a Customer Spotlight by telling the customer's story using their unique language, share what problems they experienced, and how they conquered it by using your product(s)."
            ),
            Schema::parse([
                "apiParams" => self::getApiSchema(),
            ]),
            Schema::parse([
                "userTextAlignment?" => [
                    "type" => "string",
                    "description" => t("Whether user name and title is aligned to the left or right."),
                    "default" => "left",
                    "enum" => ["left", "right"],
                ],
            ]),
            self::containerOptionsSchema("containerOptions", ["outerBackground?", "innerBackground?", "borderType?"])
        );

        return $schema;
    }

    /**
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array
    {
        $user = $this->getUserFragment($this->props["apiParams"]["userID"] ?? -1);

        if ($user === null) {
            return null;
        }

        $this->props["userInfo"] = $user;
        return $this->props;
    }
}
