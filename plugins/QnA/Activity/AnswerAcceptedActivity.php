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
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "AnswerAccepted";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "AnswerAccepted";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "My answer is accepted";
    }

    /**
     * @inheritdoc
     */
    public static function getGroupClass(): string
    {
        return \Vanilla\Dashboard\Activity\FollowedPostsActivityGroup::class;
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
        return '{ActivityUserID,You} accepted {NotifyUserID,your} answer to a question: <a href="{Url,html}">{Data.Name,text}</a>';
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return '{ActivityUserID,You} accepted {NotifyUserID,your} answer to a question: <a href="{Url,html}">{Data.Name,text}</a>';
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
        return "when your answer to a question has been accepted";
    }
}
