<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing the Community Task activity group.
 */
class CommunityTasksActivityGroup extends ActivityGroup
{
    /**
     * @inheritDoc
     */
    public static function getActivityGroupID(): string
    {
        return "communityTask";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceLabel(): string
    {
        return t("Community Tasks");
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): ?string
    {
        return t("Tasks tied to your community permissions.");
    }

    /**
     * @inheritDoc
     */
    public static function getParentGroupClass(): ?string
    {
        return NotificationsActivityGroup::class;
    }
}
