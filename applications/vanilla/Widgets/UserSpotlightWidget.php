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
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Widget to spotlight a user.
 */
class UserSpotlightWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, InjectableInterface {
    use HomeWidgetContainerSchemaTrait, CombinedPropsWidgetTrait, UserSpotlightWidgetTrait;

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "UserSpotlight";
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
    public function getComponentName(): string {
        return "UserSpotlightWidget";
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
        $params = self::getApiSchema()->validate((array)$this->props['apiParams']);
        $this->setUserID($params['userID']);
        $data = $this->getData();

        if (is_null($data) || count($data) === 0) {
            return null;
        }

        return array_merge($this->props, [
            "userInfo" => $data
        ]);
    }
}
