<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Represents the Email Digest activity.
 */
class EmailDigestActivity extends Activity
{
    const ALLOW_DEFAULT_PREFERENCE = false;

    /**
     * @inheritDoc
     */
    public static function getActivityTypeID(): string
    {
        return "EmailDigest";
    }

    /**
     * @inheritDoc
     */
    public static function getPreference(): string
    {
        return "DigestEnabled";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): string
    {
        return "Send me the email digest";
    }

    /**
     * @inheritDoc
     */
    public static function getGroupClass(): string
    {
        return EmailDigestActivityGroup::class;
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
    public static function getActivityReason(): ?string
    {
        return "with the email digest.";
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
    public static function isPublicActivity(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function getNotificationRequiredSettings(): array
    {
        return ["Garden.Digest.Enabled", "Feature.Digest.Enabled"];
    }
}
