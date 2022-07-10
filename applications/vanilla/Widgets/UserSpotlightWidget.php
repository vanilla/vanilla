<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\InjectableInterface;
use Vanilla\Layout\Section\SectionFullWidth;
use Vanilla\Layout\Section\SectionOneColumn;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;
use Vanilla\Widgets\React\SectionAwareInterface;

/**
 * Widget to spotlight a user.
 */
class UserSpotlightWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, InjectableInterface, SectionAwareInterface {
    use HomeWidgetContainerSchemaTrait, CombinedPropsWidgetTrait, UserSpotlightWidgetTrait;

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "User Spotlight";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string {
        return "userspotlight";
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string {
        return "UserSpotlightWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string {
        return "/applications/dashboard/design/images/widgetIcons/userspotlight.svg";
    }

    /**
     * @return array
     */
    public static function getRecommendedSectionIDs(): array {
        return [
            SectionFullWidth::getWidgetID(),
            SectionOneColumn::getWidgetID(),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema('subtitle'),
            self::widgetDescriptionSchema(),
            Schema::parse([
               'userTextAlignment?' => [
                   'type' => 'string',
                   'description' => t('Whether user name and title is aligned to the left or right.'),
                   'default' => 'left',
                   'enum' => [
                       'left',
                       'right',
                   ],
               ]
            ]),
            self::containerOptionsSchema("containerOptions", [
                'outerBackground?', 'innerBackground?', 'borderType?'
            ]),
            Schema::parse([
                'apiParams' => self::getApiSchema()
            ])
        );

        return $schema;
    }

    /**
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array {
        $user = $this->getUserFragment($this->props['apiParams']['userID']);

        if ($user === null) {
            return null;
        }

        $this->props['userInfo'] = $user;
        return $this->props;
    }
}
