<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Activity;

use Vanilla\Dashboard\Activity\FollowedPostsActivityGroup;
use Vanilla\Dashboard\Activity\NotificationsActivityGroup;

/**
 * Class representing the Question Answered Activity.
 */
class QuestionAnswerActivity extends \Vanilla\Dashboard\Activity\Activity
{
    /**
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "QuestionAnswer";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "QuestionAnswer";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "New answers on my question";
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
        return '{ActivityUserID,user} answered your question: <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return '{ActivityUserID,user} answered your question: <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * @inheritDoc
     */
    public static function getPluralHeadline(): ?string
    {
        return 'There are <strong>{count}</strong> new answers to <a href="{Url,html}">{Data.Name,text}</a>.';
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
        return "when people have answered your question";
    }
}
