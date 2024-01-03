<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Class representing a Followed Category Discussion activity.
 */
class CategoryDiscussionActivity extends Activity
{
    /**
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "NewDiscussion";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "NewDiscussion";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Notify me of new posts";
    }

    /**
     * @inheritDoc
     */
    public static function getGroupClass(): string
    {
        return FollowedCategoriesActivityGroup::class;
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
        return "%1\$s started a %8\$s.";
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return "%1\$s started a %8\$s.";
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
        return "when people create new posts in your category";
    }
}
