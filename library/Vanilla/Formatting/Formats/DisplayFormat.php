<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\FormatText;
use Vanilla\Formatting\ParsableDOMInterface;
use Vanilla\Formatting\TextDOMInterface;
use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;
use Vanilla\Formatting\UserMentionsTrait;

/**
 * Basic display format.
 *
 * The "display" format is a legacy name from OG Vanilla that essentially means `htmlspecialchars`. This format is a
 * quasi-implmentation of Gdn_Format::display.
 *
 * @template-implements FormatInterface<TextFormatParsed>
 */
final class DisplayFormat extends BaseFormat implements ParsableDOMInterface
{
    use UserMentionsTrait;
    const FORMAT_KEY = "display";

    /** @var string */
    protected $anonymizeUrl;

    /**
     * Generate a preparsed format.
     *
     * @param string $content
     */
    public function parse(string $content)
    {
        return new TextFormatParsed(self::FORMAT_KEY, $content);
    }

    /**
     * Ensure parsed content turns back into raw content.
     *
     * @param $rawOrParsed
     * @return string
     */
    public function ensureRawContent($rawOrParsed): string
    {
        if ($rawOrParsed instanceof TextFormatParsed) {
            return $rawOrParsed->getRawText();
        } else {
            return $rawOrParsed;
        }
    }

    /**
     * @inheritdoc
     */
    public function renderHTML($content): string
    {
        $content = $this->ensureRawContent($content);
        $result = htmlspecialchars($content, ENT_QUOTES, "UTF-8");
        $result = str_replace(["&quot;", "&amp;"], ['"', "&"], $result);
        $result = $this->applyHtmlProcessors($result);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText($content): string
    {
        $content = $this->ensureRawContent($content);
        return trim($content);
    }

    /**
     * @inheritdoc
     */
    public function filter($content): string
    {
        $content = $this->ensureRawContent($content);
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
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseDOM(string $content): TextDOMInterface
    {
        return new class ($content, $this) implements TextDOMInterface, TextFragmentInterface {
            /** @var DisplayFormat */
            private $parent;

            /** @var string */
            private $text;

            /**
             * Setup the DOM.
             *
             * @param string $text
             * @param DisplayFormat $parent
             */
            public function __construct(string $text, DisplayFormat $parent)
            {
                $this->parent = $parent;
                $this->text = $text;
            }

            /**
             * @inheritdoc
             */
            public function stringify(): FormatText
            {
                return new FormatText($this->text, DisplayFormat::FORMAT_KEY);
            }

            /**
             * @inheritdoc
             */
            public function renderHTML(): string
            {
                return $this->parent->renderHtml($this->text);
            }

            /**
             * @inheritdoc
             */
            public function getFragments(): array
            {
                return [$this];
            }

            /**
             * @inheritdoc
             */
            public function getInnerContent(): string
            {
                return $this->text;
            }

            /**
             * @inheritdoc
             */
            public function setInnerContent(string $text)
            {
                $this->text = $text;
            }

            /**
             * @inheritdoc
             */
            public function getFragmentType(): string
            {
                return TextFragmentType::TEXT;
            }
        };
    }

    /**
     * @inheritdoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        [$pattern, $replacement] = $this->getUrlReplacementPattern($username, $this->getAnonymizeUserUrl());

        $body = preg_replace($pattern, $replacement, $body);
        return $body;
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions($body): array
    {
        return [];
    }
}
