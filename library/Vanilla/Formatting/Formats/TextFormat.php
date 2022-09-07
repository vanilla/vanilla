<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\UserMentionInterface;
use Vanilla\Formatting\UserMentionsTrait;

/**
 * Class for rendering content of the markdown format.
 */
class TextFormat extends BaseFormat
{
    use UserMentionsTrait;

    const FORMAT_KEY = "text";

    /** @var FormatConfig */
    private $formatConfig;

    /** @var string */
    protected $anonymizeUsername;

    /** @var string */
    protected $anonymizeUrl;

    /**
     * @param FormatConfig $formatConfig
     */
    public function __construct(FormatConfig $formatConfig)
    {
        $this->formatConfig = $formatConfig;
        $this->anonymizeUsername = $this->getAnonymizeUserName();
        $this->anonymizeUrl = $this->getAnonymizeUserUrl();
    }

    /**
     * @inheritdoc
     */
    public function renderHTML(string $content): string
    {
        $result = html_entity_decode($content, ENT_QUOTES, "UTF-8");
        $result = preg_replace("`<br\s?/?>`", "\n", $result);
        $result = htmlspecialchars($result, ENT_NOQUOTES, "UTF-8", false);

        if ($this->formatConfig->shouldReplaceNewLines()) {
            // Added this because nl2br() doesn't replace 2nd new line if there are 2 in a row.
            $result = str_replace(["\r\n", "\n\r", "\r", "\n"], "<br /> ", trim($result));
        }

        $result = $this->applyHtmlProcessors($result);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText(string $content): string
    {
        return trim($content);
    }

    /**
     * @inheritdoc
     */
    public function filter(string $content): string
    {
        return $content;
    }

    /**
     * @inheritdoc
     */
    public function parseAttachments(string $content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImageUrls(string $content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImages(string $content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseHeadings(string $content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseMentions(string $content, bool $skipTaggedContent = true): array
    {
        // Legacy Mention Fetcher.
        // This should get replaced in a future refactoring.
        return getMentions($content, $skipTaggedContent, $skipTaggedContent);
    }

    /**
     * @inheritdoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        $pattern = [];
        $replacement = [];

        [$pattern["atMention"], $replacement["atMention"]] = $this->getNonRichAtMentionReplacePattern(
            $username,
            $this->anonymizeUsername
        );

        [$pattern["url"], $replacement["url"]] = $this->getUrlReplacementPattern($username, $this->anonymizeUrl);

        $body = preg_replace($pattern, $replacement, $body);
        return $body;
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions(string $body): array
    {
        $matches = [];
        $atMention = $this->getNonRichAtMention();
        $urlMention = $this->getUrlPattern();

        $pattern = "~($atMention|$urlMention)~";
        preg_match_all($pattern, $body, $matches, PREG_UNMATCHED_AS_NULL);
        return $this->normalizeMatches($matches);
    }
}
