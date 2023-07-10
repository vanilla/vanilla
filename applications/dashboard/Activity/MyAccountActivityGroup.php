<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Represents the My Account activity group.
 */
class MyAccountActivityGroup extends ActivityGroup
{
    /**
     * @inheritDoc
     */
    public static function getActivityGroupID(): string
    {
        return "myAccount";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceLabel(): string
    {
        return t("My Account");
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
