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
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "CommentMention";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "Mention";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "I am mentioned";
    }

    /**
     * @inheritDoc
     */
    public static function getGroupClass(): string
    {
        return FollowedPostsActivityGroup::class;
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
        return '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>';
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
    public static function getActivityReason(): ?string
    {
        return "when people mention you";
    }
}
