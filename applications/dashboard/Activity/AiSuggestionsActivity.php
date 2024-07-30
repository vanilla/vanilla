<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Activity;

use Vanilla\Dashboard\Models\AiSuggestionSourceService;

class AiSuggestionsActivity extends Activity
{
    /**
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "AiSuggestions";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "AiSuggestions";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Notify me when my questions have AI Suggested Answers";
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
        return t("{ActivityUserID,User} has suggested answers: check it out");
    }

    /**
     * @inheritDoc
     */
    public static function getFullHeadline(): ?string
    {
        return self::getProfileHeadline();
    }

    /**
     * @inheritDoc
     */
    public static function getActivityReason(): ?string
    {
        return "when your post has AI suggested answers";
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
    public static function getPreferenceSchemaProperties(): array
    {
        $parentSchema = parent::getPreferenceSchemaProperties();
        unset($parentSchema["email"]);
        return $parentSchema;
    }

    /**
     * @inheritDoc
     */
    public static function getNotificationRequiredSettings(): array
    {
        return ["aiSuggestions.enabled"];
    }
}
