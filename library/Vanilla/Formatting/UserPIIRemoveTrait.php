<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting;

use UserModel;

const END_OF_MENTION = '&nbsp;|\s|\.|,|;|\?|!|:|\'|$';

/**
 * Trait for shared PII patterns.
 */
trait UserPIIRemoveTrait
{
    /**
     * AtMention pattern used by all non-rich formats.
     *
     *
     * Supports most usernames by using double-quotes, for example:  @"a $pecial user's! name."
     * Without double-quotes, a mentioned username is terminated by any of the following characters:
     * whitespace | . | , | ; | ? | ! | : | '
     *
     * See: class.format::formatMentionsCallback()
     *
     * This cover: Wysiwyg / Html / Markdown / BBCode / TextEx / Text Mentions
     *
     * @param string $username
     * @param string $replacement
     * @return string[] [$pattern, $replacement]
     */
    public function getNonRichAtMentionPattern(string $username, string $replacement)
    {
        if (str_contains($username, " ")) {
            $pattern = "~@\"$username\"~";
            $replacement = '@"' . $replacement . '"';
        } else {
            $pattern = "~@$username(&nbsp;|\s|\.|,|;|\?|!|:|\'|$)~";
            $replacement = '@"' . $replacement . '"$1';
        }

        return [$pattern, $replacement];
    }

    /**
     * Return the pattern for the Vanilla profile URL of the user.
     *
     * @param string $username
     * @param string $replacement
     * @return string[] [$pattern, $replacement]
     */
    public function getUrlPattern(string $username, string $replacement)
    {
        $pattern = "~" . UserModel::getProfileUrl(["name" => $username]) . '(&nbsp;|\s|\.|,|;|\?|!|:|\'|$)~';
        $replacement = $replacement . '$1';
        return [$pattern, $replacement];
    }
}
