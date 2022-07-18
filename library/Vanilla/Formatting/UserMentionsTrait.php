<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting;

use UserModel;

/**
 * Trait for shared PII patterns.
 */
trait UserMentionsTrait
{
    /** @var string A regex expression which contains every token that can terminate a URL */
    protected $AFTER_URL_TOKENS = "&nbsp;|\s|\.|,|;|\?|!|:|'|$|\"|<|>|\[|\]|\(|\)";

    /** @var string A regex expression which contains every token that can terminate an at mention */
    protected $AFTER_AT_MENTION_TOKENS = "&nbsp;|\s|\.|,|;|\?|!|:|'|$";

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
    public function getNonRichAtMentionReplacePattern(string $username, string $replacement)
    {
        if (str_contains($username, " ")) {
            $pattern = "~@\"$username\"~";
            $replacement = '@"' . $replacement . '"';
        } else {
            $pattern = "~@$username({$this->AFTER_AT_MENTION_TOKENS})~";
            $replacement = '@"' . $replacement . '"$1';
        }

        return [$pattern, $replacement];
    }

    public function getUrlPattern(): string
    {
        $url = \Gdn::request()->getSimpleUrl();
        $url .= "/profile/(?<url_mentions>.+?)(?:{$this->AFTER_URL_TOKENS})";
        return $url;
    }

    public function getNonRichAtMention(): string
    {
        $pattern = "(?<!\w)@\"(?<quoted_at_mentions>[^\"]+?)\"";
        $pattern .= "|(?<!\w)@(?<at_mentions>[^\"]+?)(?={$this->AFTER_AT_MENTION_TOKENS})";

        return $pattern;
    }

    /**
     * Return the pattern for the Vanilla profile URL of the user.
     *
     * @param string $username
     * @param string $replacement
     * @return string[] [$pattern, $replacement]
     */
    public function getUrlReplacementPattern(string $username, string $replacement): array
    {
        $profileUrl = UserModel::getProfileUrl(["name" => $username]);
        $pattern = "~$profileUrl({$this->AFTER_URL_TOKENS})~";
        $replacement = $replacement . '$1';
        return [$pattern, $replacement];
    }

    /**
     * This function extracts named matches from preg_match_all and returns them as an array of usernames
     *
     * @param array $matches
     * @return string[]
     */
    protected function normalizeMatches(array $matches = []): array
    {
        $allMentions = [];
        foreach (["at_mentions", "quoted_at_mentions", "quote_mentions", "url_mentions"] as $type) {
            $currentMentions = $matches[$type] ?? [];
            foreach ($currentMentions as $mention) {
                if (is_null($mention)) {
                    continue;
                }
                if ($type === "url_mentions") {
                    $mention = rawurldecode($mention);
                }
                $allMentions[] = $mention;
            }
        }
        return $allMentions;
    }
}
