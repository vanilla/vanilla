<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\Dashboard\UserPointsModel;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\AbstractHomeWidgetModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Widget with the users having the top points.
 */
class LeaderboardWidget extends AbstractReactModule implements ReactWidgetInterface, CombinedPropsWidgetInterface {

    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;

    /** @var UserPointsModel */
    private $userPointsModel;

    /** @var \UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param UserPointsModel $userPointsModel
     * @param \UserModel $userModel
     */
    public function __construct(UserPointsModel $userPointsModel, \UserModel $userModel) {
        parent::__construct();
        $this->userPointsModel = $userPointsModel;
        $this->userModel = $userModel;
    }

    /**
     * Map a user into a widget item.
     *
     * @param array $user A full user from the database + UserPoints data.
     *
     * @return array
     */
    private function mapUserToWidgetItem(array $user): array {
        $points = $user["Points"] ?? 0;
        $user = UserFragmentSchema::normalizeUserFragment($user);

        return [
            "user" => $user,
            "points" => $points,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getProps(): ?array {
        $users = $this->userPointsModel->getLeaders(
            $this->props['apiParams']['slotType'],
            $this->props['apiParams']['categoryID'] ?? null,
            $this->props['apiParams']['limit']
        );
        if (count($users) === 0) {
            return null;
        }
        $users = array_map([$this, "mapUserToWidgetItem"], $users);
        $result = array_merge($this->props, [
            "leaders" => $users
        ]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema {
        $categoryIDSchema = Schema::parse([
            "type" => "integer",
            "default" => null,
            "description" => "The category user points should be calculated in.",
            "x-control" => DiscussionsApiIndexSchema::getCategoryIDFormOptions(),
        ]);

        $widgetSpecificSchema = Schema::parse([
            'apiParams?' => Schema::parse([
                "slotType?" => UserPointsModel::slotTypeSchema(),
                "limit?" => UserPointsModel::limitSchema(),
                'categoryID:i?' => $categoryIDSchema,
            ])
        ]);

        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema("All Time Leaders"),
            self::widgetSubtitleSchema("leaderboard"),
            self::widgetDescriptionSchema(),
            self::containerOptionsSchema("containerOptions"),
            $widgetSpecificSchema
        );
    }

    /**
     * @inheritDoc
     */
    public function getComponentName(): string {
        return "LeaderboardWidget";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string {
        return "leaderboard";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "Leaderboard";
    }
}
