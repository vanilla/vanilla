<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Represents the Followed Categories activity group.
 */
class FollowedCategoriesActivityGroup extends ActivityGroup
{
    /**
     * @inheritDoc
     */
    public static function getActivityGroupID(): string
    {
        return "followedCategories";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceLabel(): string
    {
        return "Categories";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): ?string
    {
        $url = "/profile/followed-content";
        $descriptionString = t(
            'Default notification settings can be modified for each followed category in <a href="{url,html}">Manage Followed Categories</a>.'
        );
        $formattedString = formatString($descriptionString, ["url" => $url]);
        return $formattedString;
    }

    /**
     * @inheritDoc
     */
    public static function getParentGroupClass(): ?string
    {
        return NotificationsActivityGroup::class;
    }
}
