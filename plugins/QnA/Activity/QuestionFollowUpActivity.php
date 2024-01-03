<?php

/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Activity;

/**
 * Represents the QuestionFollowUp Activity.
 */
class QuestionFollowUpActivity extends \Vanilla\Dashboard\Activity\Activity
{
    /**
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "QuestionFollowUp";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "QuestionFollowUp";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Send me a follow-up for my answered questions";
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
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return null;
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

    public static function getPreferenceSchemaProperties(): array
    {
        $properties = [
            "email" => ["type" => "boolean", "x-control" => ["inputType" => "checkBox", "label" => t("Email")]],
            "disabled" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => ["inputType" => "checkBox", "label" => t("Disabled")],
            ],
        ];

        return $properties;
    }

    /**
     * @inheritDoc
     */
    public static function getActivityReason(): ?string
    {
        return "when there has been a follow-up to your answered questions";
    }
}
