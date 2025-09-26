<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Represents a wall comment activity.
 */
class WallCommentActivity extends Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "WallComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "WallComment";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "New posts on my profile's activity feed";
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
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function getProfileHeadline(): ?string
    {
        return "%1\$s wrote:";
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s wrote on %4\$s %5\$s.";
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

    /**
     * @inheritdoc
     */
    public static function getActivityReason(): ?string
    {
        return "when people write on your wall";
    }
}
