<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing the ParticipateComment activity.
 */
class ParticipateCommentActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "ParticipateComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "ParticipateComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "New comments on posts I've participated in";
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
        return "%1\$s commented on %4\$s %8\$s.";
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
        return "when people comment on posts you have participated in";
    }
}
