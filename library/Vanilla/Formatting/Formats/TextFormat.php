<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\FormatRegexReplacements;
use Vanilla\Formatting\UserMentionInterface;
use Vanilla\Formatting\UserMentionsTrait;

/**
 * Class for rendering content of the plaintext format.
 *
 * @template-implements FormatInterface<TextFormatParsed>
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
    public function parse(string $content)
    {
        return new TextFormatParsed(static::FORMAT_KEY, $content);
    }

    /**
     * Given either raw text or "parsed" raw text, pull out the raw text.
     *
     * @param $contentOrParsed
     * @return string
     */
    protected function ensureRaw($contentOrParsed): string
    {
        if ($contentOrParsed instanceof TextFormatParsed) {
            return $contentOrParsed->getRawText();
        } else {
            return $contentOrParsed;
        }
    }

    /**
     * @inheritdoc
     */
    public function renderHTML($content): string
    {
        $content = $this->ensureRaw($content);
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
    public function renderPlainText($content): string
    {
        $content = $this->ensureRaw($content);
        return trim($content);
    }

    /**
     * @inheritdoc
     */
    public function filter($content): string
    {
        $content = $this->ensureRaw($content);
        return $content;
    }

    /**
     * @inheritdoc
     */
    public function parseAttachments($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImageUrls($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImages($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseHeadings($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseMentions($content, bool $skipTaggedContent = true): array
    {
        $content = $this->ensureRaw($content);
        // Legacy Mention Fetcher.
        // This should get replaced in a future refactoring.
        return getMentions($content, $skipTaggedContent, $skipTaggedContent);
    }

    /**
     * @inheritdoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        $regex = new FormatRegexReplacements();
        $regex->addReplacement(...$this->getNonRichAtMentionReplacePattern($username, $this->anonymizeUsername));
        $regex->addReplacement(...$this->getUrlReplacementPattern($username, $this->anonymizeUrl));
        return $regex->replace($body);
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions($body): array
    {
        $body = $this->ensureRaw($body);

        return $this->getNonRichMentions($body);
    }
}
