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
     * @inheritDoc
     */
    public static function getActivityGroupID(): string
    {
        return "emailDigest";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceLabel(): string
    {
        return "Weekly Email Digest";
    }

    /**
     * @inheritDoc
     */
    public static function getPreferenceDescription(): ?string
    {
        $url = url("/profile/followed-content", true);
        $descriptionString =
            'The email digest delivers the week\'s top content from the categories you follow into your email inbox once per week. <a href="{url,html}">Manage Followed Categories</a>.';
        $formattedString = formatString($descriptionString, ["url" => $url]);
        return $formattedString;
    }

    /**
     * @inheritDoc
     */
    public static function getParentGroupClass(): ?string
    {
        return null;
    }
}
