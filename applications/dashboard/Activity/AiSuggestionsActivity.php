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
     * @inheritdoc
     */
    public static function getActivityTypeID(): string
    {
        return "AiSuggestions";
    }

    /**
     * @inheritdoc
     */
    public static function getPreference(): string
    {
        return "AiSuggestions";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Notify me when my questions have AI Suggested Answers";
    }

    /**
     * @inheritdoc
     */
    public static function getGroupClass(): string
    {
        return FollowedPostsActivityGroup::class;
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
        return t("{ActivityUserID,User} has suggested answers: check it out");
    }

    /**
     * @inheritdoc
     */
    public static function getFullHeadline(): ?string
    {
        return self::getProfileHeadline();
    }

    /**
     * @inheritdoc
     */
    public static function getActivityReason(): ?string
    {
        return "when your post has AI suggested answers";
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
    public static function getPreferenceSchemaProperties(): array
    {
        $parentSchema = parent::getPreferenceSchemaProperties();
        unset($parentSchema["email"]);
        return $parentSchema;
    }

    /**
     * @inheritdoc
     */
    public static function getNotificationRequiredSettings(): array
    {
        return ["aiSuggestions.enabled"];
    }
}
