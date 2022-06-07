<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Dashboard\Models\UserLeaderQuery;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Dashboard\UserPointsModel;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Layout\Section\SectionThreeColumns;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Widget with the users having the top points.
 */
class LeaderboardWidget extends AbstractReactModule implements
    ReactWidgetInterface,
    CombinedPropsWidgetInterface,
    SectionAwareInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;

    /** @var UserLeaderService */
    private $userLeaderService;

    /** @var \UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param UserLeaderService $userLeaderService
     * @param \UserModel $userModel
     */
    public function __construct(UserLeaderService $userLeaderService, \UserModel $userModel)
    {
        parent::__construct();
        $this->userLeaderService = $userLeaderService;
        $this->userModel = $userModel;
    }

    /**
     * Map a user into a widget item.
     *
     * @param array $user A full user from the database + UserPoints data.
     *
     * @return array
     */
    private function mapUserToWidgetItem(array $user): array
    {
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
    public function getProps(): ?array
    {
        $query = new UserLeaderQuery(
            $this->props["apiParams"]["slotType"],
            $this->props["apiParams"]["categoryID"] ?? null,
            $this->props["apiParams"]["limit"]
        );
        $users = $this->userLeaderService->getLeaders($query);
        if (count($users) === 0) {
            return null;
        }
        $users = array_map([$this, "mapUserToWidgetItem"], $users);
        $result = array_merge($this->props, [
            "leaders" => $users,
        ]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        $categoryIDSchema = Schema::parse([
            "type" => "integer",
            "default" => null,
            "description" => "The category user points should be calculated in.",
            "x-control" => DiscussionsApiIndexSchema::getCategoryIDFormOptions(),
        ]);

        $widgetSpecificSchema = Schema::parse([
            "apiParams?" => Schema::parse([
                "slotType?" => UserPointsModel::slotTypeSchema(),
                "LeaderboardType?" => UserPointsModel::leaderboardTypeSchema(),
                "limit?" => UserPointsModel::limitSchema(),
                "categoryID:i?" => $categoryIDSchema,
            ]),
        ]);

        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema("All Time Leaders"),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            $widgetSpecificSchema,
            self::containerOptionsSchema("containerOptions")
        );
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "LeaderboardWidget";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "leaderboard";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Leaderboard";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/leaderboard.svg";
    }

    /**
     * @return array
     */
    public static function getRecommendedSectionIDs(): array
    {
        return [SectionTwoColumns::getWidgetID(), SectionThreeColumns::getWidgetID()];
    }
}
