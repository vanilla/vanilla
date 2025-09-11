<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Notification sent when content is reported.
 */
class ReportActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "Report";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "report";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Notify me of new reports";
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
        return "%1\$s reported %2\$s.";
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s reported %2\$s.";
    }

    /**
     * @inheritdoc
     */
    public static function getActivityReason(): ?string
    {
        return "A new report has been submitted.";
    }

    /**
     * @inheritdoc
     */
    public static function getPluralHeadline(): ?string
    {
        return "There are <strong>{count}</strong> new reports on %1\$s.";
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
        return ["Garden.Moderation.Manage"];
    }

    /**
     * Prevent this activity from showing up as an option without the feature flag.
     *
     * TODO: Remove this when we are ready to launch CMD.
     */
    public static function getNotificationRequiredSettings(): array
    {
        return ["Feature.CommunityManagementBeta.Enabled"];
    }
}
