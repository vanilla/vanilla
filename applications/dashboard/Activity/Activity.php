<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Base class for Activities
 */
abstract class Activity
{
    const ALLOW_DEFAULT_PREFERENCE = true;

    /**
     * Get the name of the activity type. This is functionally the primary key.
     *
     * @return string
     */
    abstract public static function getActivityTypeID(): string;

    /**
     * Get the notification preference label of the activity type.
     *
     * @return string
     */
    abstract public static function getPreference(): string;

    /**
     * Get the notification preference description of the activity type.
     *
     * @return string
     */
    abstract public static function getPreferenceDescription(): string;

    /**
     * Get the class name of the activity group to which this type belongs.
     *
     * @return class-string<ActivityGroup>|null
     *
     * getGroupClass
     */
    abstract public static function getGroupClass(): string;

    /**
     * Whether this activity allows comments.
     *
     * @return bool
     */
    abstract public static function allowsComments(): bool;

    /**
     * Get the activity's profile headline
     *
     * @return string|null
     */
    abstract public static function getProfileHeadline(): ?string;

    /**
     * Get the activity's full profile headline.
     *
     * @return string|null
     */
    abstract public static function getFullHeadline(): ?string;

    /**
     * Get the activity's reason for being sent.
     *
     * @return string|null
     */
    abstract public static function getActivityReason(): ?string;

    /**
     * Get the plural form of the activity's headline.
     *
     * @return string|null
     */
    abstract public static function getPluralHeadline(): ?string;

    /**
     * Whether users can be notified of the activity.
     *
     * @return bool
     */
    abstract public static function isNotificationType(): bool;

    /**
     * Whether the activity can be viewed publicly.
     *
     * @return bool
     */
    abstract public static function isPublicActivity(): bool;

    /**
     * Get the schema properties for the notification preference.
     *
     * @return array[]
     */
    public static function getPreferenceSchemaProperties(): array
    {
        $properties = [
            "popup" => [
                "type" => "boolean",
                "x-control" => ["inputType" => "checkBox", "label" => t("Notification popup")],
            ],
            "email" => ["type" => "boolean", "x-control" => ["inputType" => "checkBox", "label" => t("Email")]],
            "disabled" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => ["inputType" => "checkBox", "label" => t("Disabled")],
            ],
        ];

        return $properties;
    }

    /**
     * Get the schema's required properties
     *
     * @return array[]
     */
    public static function getPreferenceSchemaRequiredProperties(): array
    {
        return [];
    }

    /**
     * Get any permissions required for receiving notifications related to this activity.
     *
     * @return array
     */
    public static function getNotificationPermissions(): array
    {
        return [];
    }

    /**
     * Get any site settings required to receiving notifications related to this activity.
     *
     * @return array
     */
    public static function getNotificationRequiredSettings(): array
    {
        return [];
    }
}
