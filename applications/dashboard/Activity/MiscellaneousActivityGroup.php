<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing a group for miscellaneous activities. This group intentionally has no label or description.
 */
class MiscellaneousActivityGroup extends ActivityGroup
{
    /**
     * @inheritdoc
     */
    public static function getActivityGroupID(): string
    {
        return "miscellaneous";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceLabel(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): ?string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public static function getParentGroupClass(): ?string
    {
        return NotificationsActivityGroup::class;
    }
}
