<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Represents and activity comment activity.
 */
class ActivityCommentActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "ActivityComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "ActivityComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "New comments on my activity feed posts";
    }

    /**
     * @inheritdoc
     */
    public static function getGroupClass(): string
    {
        return MyAccountActivityGroup::class;
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
        return "%1\$s";
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s commented on %4\$s %8\$s.";
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
        return true;
    }

    public static function getActivityReason(): ?string
    {
        return "when people reply to your wall comments";
    }
}
