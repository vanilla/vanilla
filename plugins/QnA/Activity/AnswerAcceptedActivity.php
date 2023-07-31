<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Activity;

/**
 * Class representing the Answer Accepted activity.
 */
class AnswerAcceptedActivity extends \Vanilla\Dashboard\Activity\Activity
{
    /**
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "AnswerAccepted";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "AnswerAccepted";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "My answer is accepted";
    }

    /**
     * @inheritDoc
     */
    public static function getGroupClass(): string
    {
        return \Vanilla\Dashboard\Activity\FollowedPostsActivityGroup::class;
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
        return '{ActivityUserID,You} accepted {NotifyUserID,your} answer to a question: <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return '{ActivityUserID,You} accepted {NotifyUserID,your} answer to a question: <a href="{Url,html}">{Data.Name,text}</a>';
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
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function getActivityReason(): ?string
    {
        return "when your answer to a question has been accepted";
    }
}
