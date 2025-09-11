<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use UserModel;
use Vanilla\Dashboard\Models\UserLeaderQuery;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Dashboard\UserPointsModel;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\LegacyReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Widget with the users having the top points.
 */
class LeaderboardWidget extends LegacyReactModule implements CombinedPropsWidgetInterface
{
    use HomeWidgetContainerSchemaTrait;
    use FilterableWidgetTrait;
    use CombinedPropsWidgetTrait;

    /**
     * DI.
     */
    public function __construct(private UserLeaderService $userLeaderService, private UserModel $userModel)
    {
        parent::__construct();
    }

    /**
     * @return string
     */
    public static function getWidgetGroup(): string
    {
        return "Members";
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
        $points = $user["Points"] ?? ($user["points"] ?? 0);
        $user = UserFragmentSchema::normalizeUserFragment($user);

        return [
            "user" => $user,
            "points" => $points,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        $categoryID = null;
        $siteSectionID = null;

        switch ($this->props["apiParams"]["filter"]) {
            case "subcommunity":
                $this->props["apiParams"]["filter"] = "siteSection";

                $siteSectionID = $this->props["apiParams"]["siteSectionID"] ?? null;
                break;
            case "category":
                $this->props["apiParams"]["categoryID"] =
                    $this->props["apiParams"]["categoryID"] ?? ($this->props["apiParams"]["parentRecordID"] ?? null);

                unset($this->props["apiParams"]["filterSubcommunitySubType"]);
                unset($this->props["apiParams"]["filterCategorySubType"]);
                unset($this->props["apiParams"]["parentRecordType"]);
                unset($this->props["apiParams"]["parentRecordID"]);
                unset($this->props["apiParams"]["siteSectionID"]);

                $categoryID = $this->props["apiParams"]["categoryID"];

                break;
            case "none":
                unset($this->props["apiParams"]["categoryID"]);
                unset($this->props["apiParams"]["filterSubcommunitySubType"]);
                unset($this->props["apiParams"]["filterCategorySubType"]);
                break;
        }

        $query = new UserLeaderQuery(
            $this->props["apiParams"]["slotType"],
            $categoryID,
            $siteSectionID,
            $this->props["apiParams"]["limit"],
            $this->props["apiParams"]["includedRoleIDs"] ?? null,
            $this->props["apiParams"]["excludedRoleIDs"] ?? null,
            $this->props["apiParams"]["leaderboardType"] ?? null
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
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $content = "";
        foreach ($props["leaders"] as $leader) {
            $row =
                "<div class='row'>" . $this->renderSeoUser($leader["user"]) . "<span>{$leader["points"]}</span></div>";
            $content .= $row;
        }
        $result = $this->renderWidgetContainerSeoContent($props, $content);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $includedRolesIDsSchema = Schema::parse([
            "x-no-hydrate" => true,
            "description" => "Roles to include to the leaderboard.",
            "type" => "array",
            "default" => [],
            "x-control" => SchemaForm::dropDown(
                new FormOptions(t("Included Roles"), t("Roles to include to the leaderboard."), t("All")),
                new ApiFormChoices("/api/v2/roles", "/api/v2/roles/%s", "roleID", "name"),
                null,
                true
            ),
        ]);

        $excludedRolesIDsSchema = Schema::parse([
            "x-no-hydrate" => true,
            "description" => "Roles to exclude from the leaderboard.",
            "type" => "array",
            "default" => [],
            "x-control" => SchemaForm::dropDown(
                new FormOptions(t("Excluded Roles"), t("Roles to exclude from the leaderboard."), t("All")),
                new ApiFormChoices("/api/v2/roles", "/api/v2/roles/%s", "roleID", "name"),
                null,
                true
            ),
        ]);

        $limitSchema = Schema::parse([
            "type" => "integer",
            "minimum" => 1,
            "maximum" => 100,
            "step" => 1,
            "default" => 10,
            "description" => t("Desired number of items."),
            "x-control" => SchemaForm::textBox(
                new FormOptions(
                    t("Limit"),
                    t("Choose how many records to display."),
                    "",
                    t("Up to a maximum of 100 items may be displayed.")
                ),
                "number"
            ),
        ]);

        $apiParams["slotType?"] = UserPointsModel::slotTypeSchema();
        $apiParams["leaderboardType?"] = UserPointsModel::leaderboardTypeSchema();

        $apiParams["limit"] = $limitSchema;
        $apiParams["includedRoleIDs?"] = $includedRolesIDsSchema;
        $apiParams["excludedRoleIDs?"] = $excludedRolesIDsSchema;

        // We may have a provided `layoutViewType`, or not.
        switch ($_REQUEST["layoutViewType"] ?? false) {
            case "home":
                $filterTypeSchemaExtraOptions = [
                    "hasSubcommunitySubTypeOptions" => false,
                    "hasCategorySubTypeOptions" => false,
                ];
                break;
            case "subcommunityHome":
            case "discussionList":
                $filterTypeSchemaExtraOptions = [
                    "hasCategorySubTypeOptions" => false,
                ];
                break;
            case "categoryList":
            case "discussionCategoryPage":
            case "nestedCategoryList":
            default:
                $filterTypeSchemaExtraOptions = [];
                break;
        }

        $widgetSpecificSchema = Schema::parse([
            "apiParams?" => SchemaUtils::composeSchemas(
                Schema::parse($apiParams),
                self::filterTypeSchema(["subcommunity", "category", "none"], false, $filterTypeSchemaExtraOptions)
            ),
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
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "LeaderboardWidget";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "leaderboard";
    }

    /**
     * @inheritdoc
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
}
