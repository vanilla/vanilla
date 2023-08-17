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
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "WallComment";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "WallComment";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "New posts on my profile's activity feed";
    }

    /**
     * @inheritDoc
     */
    public static function getGroupClass(): string
    {
        return MyAccountActivityGroup::class;
    }

    /**
     * @inheritDoc
     */
    public static function allowsComments(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getProfileHeadline(): ?string
    {
        return "%1\$s wrote:";
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s wrote on %4\$s %5\$s.";
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
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getActivityReason(): ?string
    {
        return "when people write on your wall";
    }
}
