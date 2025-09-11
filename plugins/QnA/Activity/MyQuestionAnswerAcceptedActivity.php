<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Activity;

use Vanilla\Dashboard\Activity\Activity;
use Vanilla\Dashboard\Activity\FollowedPostsActivityGroup;

/**
 * Activity for when an answer is accepted on a bookmarked question
 */
class MyQuestionAnswerAcceptedActivity extends Activity
{
    /**
     * Returns the activity type ID for this activity.
     *
     * @return string
     */
    public static function getActivityTypeID(): string
    {
        return "MyQuestionAnswerAccepted";
    }

    /**
     * Returns the preference key for this activity type.
     *
     * @return string
     */
    public static function getPreference(): string
    {
        return "MyQuestionAnswerAccepted";
    }

    /**
     * Returns the description for the preference associated with this activity type.
     *
     * @return string
     */
    public static function getPreferenceDescription(): string
    {
        return "An answer was accepted on my question";
    }

    /**
     * Returns the class name of the activity group that this activity belongs to.
     *
     * @return string
     */
    public static function getGroupClass(): string
    {
        return FollowedPostsActivityGroup::class;
    }

    /**
     * Indicates whether this activity type allows comments.
     *
     * @return bool
     */
    public static function allowsComments(): bool
    {
        return false;
    }

    /**
     * Returns the headline for the activity type.
     *
     * @return string|null
     */
    public static function getProfileHeadline(): ?string
    {
        return '{ActivityUserID,You} accepted an answer on your question: <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * Returns the full headline for the activity type, including additional context.
     *
     * @return string|null
     */
    public static function getFullHeadline(): ?string
    {
        return '{ActivityUserID,You} accepted an answer on your question: <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * Returns the reason for the activity, which is used in notifications and activity streams.
     *
     * @return string|null
     */
    public static function getActivityReason(): ?string
    {
        return "when a comment is chosen as an appropriate answer on your questions";
    }

    /**
     * Returns the plural headline for the activity type, used when multiple activities are displayed together.
     *
     * @return string|null
     */
    public static function getPluralHeadline(): ?string
    {
        return null;
    }

    /**
     * Indicates whether this activity type is a notification type.
     *
     * @return bool
     */
    public static function isNotificationType(): bool
    {
        return true;
    }

    /**
     * Indicates whether this activity type is public.
     *
     * @return bool
     */
    public static function isPublicActivity(): bool
    {
        return false;
    }

    /**
     * Returns the permissions required for this activity type to be visible in notifications.
     *
     * @return string[]
     */
    public static function getNotificationPermissions(): array
    {
        return ["Garden.SignIn.Allow"];
    }
}
