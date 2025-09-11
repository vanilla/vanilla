<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Activity;

use Vanilla\Dashboard\Activity\MyAccountActivityGroup;

/**
 * Class representing the Conversation Message activity.
 */
class ConversationMessageActivity extends \Vanilla\Dashboard\Activity\Activity
{
    /**
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "ConversationMessage";
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
        return '{ActivityUserID,User} sent you a <a href="{Url,html}">message</a>';
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return '{ActivityUserID,User} sent you a <a href="{Url,html}">message</a>';
    }

    /**
     * @inheritdoc
     */
    public static function getPluralHeadline(): ?string
    {
        return '{ActivityUserID,User} sent you {count} <a href="{Url,html}">messages</a>';
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
