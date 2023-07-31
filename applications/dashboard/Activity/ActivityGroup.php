<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Base class for Activity groups.
 */
abstract class ActivityGroup
{
    /**
     * Get the name of the activity group. This functions as a primary key.
     *
     * @return string
     *
     * getActivityGroupID
     */
    abstract public static function getActivityGroupID(): string;

    /**
     * Get the notification preference label of the activity group.
     *
     * @return string
     */
    abstract public static function getPreferenceLabel(): string;

    /**
     * Get the notification preference description of the activity group.
     *
     * @return string|null
     */
    abstract public static function getPreferenceDescription(): ?string;

    /**
     * Get the class name of the parent group to which this group belongs.
     *
     * @return class-string<ActivityGroup>|null returns null if the group has no parent group.
     */
    abstract public static function getParentGroupClass(): ?string;
}
