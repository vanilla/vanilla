<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Activity;

use Vanilla\Dashboard\Activity\MyAccountActivityGroup;

/**
 * Class representing the Added to Conversation activity.
 */
class AddedToConversationActivity extends \Vanilla\Dashboard\Activity\Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "AddedToConversation";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "ConversationMessage";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Private messages";
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
        return t(
            "You were added to a conversation.",
            '{ActivityUserID,User} added you to a <a href="{Url,htmlencode}">conversation</a>.'
        );
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return t(
            "You were added to a conversation.",
            '{ActivityUserID,User} added you to a <a href="{Url,htmlencode}">conversation</a>.'
        );
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
        return "of private messages";
    }
}
