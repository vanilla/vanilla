<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

use Gdn;

/**
 * Represents the Followed Categories activity group.
 */
class FollowedCategoriesActivityGroup extends ActivityGroup
{
    /**
     * @inheritdoc
     */
    public static function getActivityGroupID(): string
    {
        return "followedCategories";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceLabel(): string
    {
        return "Categories";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): ?string
    {
        $user = Gdn::session()->User;
        $url = userUrl($user, "", "followed-content");
        $descriptionString =
            'Default notification settings can be modified for each followed category in <a href="{url,html}">Manage Followed Content</a>.';
        return formatString($descriptionString, ["url" => $url]);
    }

    /**
     * @inheritdoc
     */
    public static function getParentGroupClass(): ?string
    {
        return NotificationsActivityGroup::class;
    }
}
