<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing the Notifications activity group.
 */
class NotificationsActivityGroup extends ActivityGroup
{
    /**
     * @inheritDoc
     */
    public static function getActivityGroupID(): string
    {
        return "notifications";
    }

    public static function getPreferenceLabel(): string
    {
        return t("Notifications");
    }

    public static function getPreferenceDescription(): ?string
    {
        return t("Choose to be notified by notification popup or email.");
    }

    /**
     * @inheritDoc
     */
    public static function getParentGroupClass(): ?string
    {
        return null;
    }
}
