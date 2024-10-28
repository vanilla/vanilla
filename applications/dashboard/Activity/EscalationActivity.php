<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Notification sent when content is escalated.
 */
class EscalationActivity extends Activity
{
    /**
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "Escalation";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "escalation";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Notify me of new Escalations";
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
        return "%1\$s has been escalated.";
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s has been escalated.";
    }

    /**
     * @inheritDoc
     */
    public static function getActivityReason(): ?string
    {
        return "A post has been escalated to the Community Management Dashboard.";
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
        return ["Garden.Moderation.Manage"];
    }

    /**
     * Prevent this activity from showing up as an option.
     *
     * TODO: Remove this when we are ready to launch CMD.
     */
    public static function getNotificationRequiredSettings(): array
    {
        return ["Feature.CommunityManagementBeta.Enabled"];
    }
}
