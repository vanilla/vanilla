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
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "QuestionFollowUp";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "QuestionFollowUp";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Send me a follow-up for my answered questions";
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
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return null;
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
     * @inheritdoc
     */
    public static function getActivityReason(): ?string
    {
        return "when there has been a follow-up to your answered questions";
    }
}
