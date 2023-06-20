<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing the Followed Posts activity group.
 */
class FollowedPostsActivityGroup extends ActivityGroup
{
    /**
     * @inheritDoc
     */
    public static function getActivityGroupID(): string
    {
        return "followedPosts";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceLabel(): string
    {
        return t("Followed Posts");
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function getParentGroupClass(): ?string
    {
        return NotificationsActivityGroup::class;
    }
}
