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
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "ParticipateComment";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "ParticipateComment";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "New comments on posts I've participated in";
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
        return "%1\$s commented on %4\$s %8\$s.";
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return self::getProfileHeadline();
    }

    /**
     * @inheritDoc
     */
    public static function getPluralHeadline(): ?string
    {
        return 'There are <strong>{count}</strong> new comments on <a href="{Url,html}">{Data.Name,text}</a>.';
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
        return "when people comment on posts you have participated in";
    }
}
