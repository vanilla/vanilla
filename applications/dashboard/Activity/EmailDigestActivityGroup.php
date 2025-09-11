<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Activity;

/**
 * Represents the Email Digest activity group.
 */
class EmailDigestActivityGroup extends ActivityGroup
{
    /**
     * @inheritdoc
     */
    public static function getActivityGroupID(): string
    {
        return "emailDigest";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceLabel(): string
    {
        return "Email Digest";
    }

    /**
     * @inheritdoc
     */
    public static function getPreferenceDescription(): ?string
    {
        $url = url("/profile/followed-content", true);
        $isGroupsEnabled = \Gdn::config("EnabledApplications.Groups") === "groups";

        $descriptionString = $isGroupsEnabled
            ? "The email digest delivers top content from the categories and groups you follow—straight to your inbox."
            : "The email digest delivers top content from the categories you follow—straight to your inbox.";

        $descriptionString .= " <a href='{url,html}'>" . t("Manage Followed Content") . "</a>";

        $formattedString = formatString($descriptionString, ["url" => $url]);
        return $formattedString;
    }

    /**
     * @inheritdoc
     */
    public static function getParentGroupClass(): ?string
    {
        return null;
    }
}
