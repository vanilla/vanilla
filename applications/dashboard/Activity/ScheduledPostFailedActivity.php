<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Activity;

class ScheduledPostFailedActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "ScheduledPostFailure";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "ScheduledPostFailure";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Notify me if a scheduled post fails to publish";
    }

    /**
     * @inheritdoc
     */
    public static function getGroupClass(): string
    {
        return FollowedPostsActivityGroup::class;
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
        return t("Scheduled post: {Data.Name} has failed to publish.");
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return self::getProfileHeadline();
    }

    /**
     * @inheritdoc
     */
    public static function getActivityReason(): ?string
    {
        return "when your scheduled post fails to publish";
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
    public static function getNotificationRequiredSettings(): array
    {
        return ["Feature.DraftScheduling.Enabled"];
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceSchemaProperties(): array
    {
        return [];
    }
}
