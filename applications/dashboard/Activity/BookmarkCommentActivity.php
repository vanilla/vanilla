<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing the "BookmarkComment" activity.
 */
class BookmarkCommentActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "BookmarkComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "BookmarkComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "New comments on my bookmarked posts";
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
        return "%1\$s commented on your %8\$s.";
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s commented on your %8\$s.";
    }

    /**
     * @inheritdoc
     */
    public static function getPluralHeadline(): ?string
    {
        return 'There are <strong>{count}</strong> new comments on <a href="{Url,html}">{Data.Name,text}</a>.';
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
        return "when people comment on your bookmarked posts";
    }
}
