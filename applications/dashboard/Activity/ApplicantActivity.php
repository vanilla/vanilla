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
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "Applicant";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "Applicant";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Membership requests";
    }

    /**
     * @inheritDoc
     */
    public static function getGroupClass(): string
    {
        return CommunityTasksActivityGroup::class;
    }

    /**
     * @inheritDoc
     */
    public static function allowsComments(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function getProfileHeadline(): ?string
    {
        return "%1\$s applied for membership.";
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s applied for membership.";
    }

    /**
     * @inheritDoc
     */
    public static function getPluralHeadline(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function isNotificationType(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function isPublicActivity(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function getNotificationPermissions(): array
    {
        return ["Garden.Users.Approve"];
    }

    /**
     * @inheritDoc
     */
    public static function getNotificationRequiredSettings(): array
    {
        return [["Garden.Registration.Method" => "Approval"]];
    }

    /**
     * @inheritDoc
     */
    public static function getActivityReason(): ?string
    {
        return "users apply for membership.";
    }
}
