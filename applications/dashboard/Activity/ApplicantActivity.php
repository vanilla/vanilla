<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing the "Applicant" activity.
 */
class ApplicantActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "Applicant";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "Applicant";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Membership requests";
    }

    /**
     * @inheritdoc
     */
    public static function getGroupClass(): string
    {
        return CommunityTasksActivityGroup::class;
    }

    /**
     * @inheritdoc
     */
    public static function allowsComments(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getProfileHeadline(): ?string
    {
        return "%1\$s applied for membership.";
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s applied for membership.";
    }

    /**
     * @inheritdoc
     */
    public static function getPluralHeadline(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function isNotificationType(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isPublicActivity(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getNotificationPermissions(): array
    {
        return ["Garden.Users.Approve"];
    }

    /**
     * @inheritdoc
     */
    public static function getNotificationRequiredSettings(): array
    {
        return [["Garden.Registration.Method" => "Approval"]];
    }

    /**
     * @inheritdoc
     */
    public static function getActivityReason(): ?string
    {
        return "users apply for membership.";
    }
}
