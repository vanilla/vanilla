<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

use Vanilla\Forum\Digest\DigestModel;

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
        $frequencyOptions = DigestModel::DIGEST_FREQUENCY_OPTIONS;
        $properties = [
            "email" => [
                "type" => "boolean",
                "x-control" => ["inputType" => "checkBox", "label" => t(self::getPreferenceDescription())],
            ],
            "disabled" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => ["inputType" => "checkBox", "label" => t("Disabled")],
            ],
        ];

        $choices = [];

        foreach ($frequencyOptions as $frequency) {
            $choices[$frequency] = t(ucfirst($frequency));
        }

        $properties["frequency"] = [
            "default" => self::getDefaultFrequency(),
            "type" => "string",
            "enum" => $frequencyOptions,
            "x-control" => [
                "inputType" => "dropDown",
                "label" => t("Digest Frequency"),
                "choices" => [
                    "staticOptions" => $choices,
                ],
                "conditions" => [
                    [
                        "field" => self::getPreference() . "." . "email",
                        "type" => "boolean",
                        "const" => true,
                    ],
                ],
            ],
        ];

        return $properties;
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceSchemaRequiredProperties(): array
    {
        return ["frequency"];
    }

    /**
     * Get the default digest frequency.
     * If the configured value is not among the valid frequency options, then the default value -- `weekly -- is returned
     * @return string
     */
    public static function getDefaultFrequency(): string
    {
        $defaultFrequency = DigestModel::DIGEST_TYPE_WEEKLY;

        $configuredDefaultFrequency = \Gdn::config(DigestModel::DEFAULT_DIGEST_FREQUENCY_KEY);
        if (
            $configuredDefaultFrequency &&
            in_array($configuredDefaultFrequency, DigestModel::DIGEST_FREQUENCY_OPTIONS)
        ) {
            $defaultFrequency = $configuredDefaultFrequency;
        }

        return $defaultFrequency;
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
        return ["Garden.Digest.Enabled"];
    }
}
