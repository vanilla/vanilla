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
     * @inheritdoc
     */
    public static function getActivityGroupID(): string
    {
        return "myAccount";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceLabel(): string
    {
        return t("My Account");
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function getParentGroupClass(): ?string
    {
        return NotificationsActivityGroup::class;
    }
}
