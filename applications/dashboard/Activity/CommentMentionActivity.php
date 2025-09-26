<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Represents the Comment Mention activity.
 */
class CommentMentionActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "CommentMention";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "Mention";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "I am mentioned";
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
        return '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>';
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
    public static function getActivityReason(): ?string
    {
        return "when people mention you";
    }
}
