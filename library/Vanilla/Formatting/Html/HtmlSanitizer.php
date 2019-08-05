<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html;

/**
 * Class for sanitizing HTML.
 */
class HtmlSanitizer {

    /** @var \VanillaHtmlFormatter */
    private $htmlFilterer;

    /** @var bool */
    private $shouldEncodeCodeBlocks = true;

    /**
     * DI.
     *
     * @param \VanillaHtmlFormatter $htmlFilterer
     */
    public function __construct(\VanillaHtmlFormatter $htmlFilterer) {
        $this->htmlFilterer = $htmlFilterer;
    }

    /**
     * Filter some potentially dangerous user content.
     *
     * - If HMTL tags are found will filter using HtmLawed.
     * - Otherwise HTML encodes the content.
     *
     * @param string $content
     * @return string
     */
    public function filter(string $content): string {
        if (!self::containsHtmlTags($content)) {
            return htmlspecialchars($content);
        }

        $encodedCodeBlocks = $this->encodeCodeBlocks($content);

        $options = [
            'codeBlockEntities' => false,
            'spec' => [
                'span' => [
                    'style' => ['match' => '/^(color:(#[a-f\d]{3}[a-f\d]{3}?|[a-z]+))?;?$/i']
                ]
            ]
        ];
        return $this->htmlFilterer->format($encodedCodeBlocks, $options);
    }

    /**
     * Set whether or not the sanitizer should encode the contents of code blocks.
     *
     * Set this to false if the input to sanitizer has already encoded them.
     *
     * @param bool $shouldEncodeCodeBlocks
     */
    public function setShouldEncodeCodeBlocks(bool $shouldEncodeCodeBlocks) {
        $this->shouldEncodeCodeBlocks = $shouldEncodeCodeBlocks;
    }

    /**
     * Quickly determine if a string contains any HTML content that would need to be purified.
     *
     * This airs on returning false positives, rather than false negatives.
     *
     * @param string $toCheck The content to check.
     *
     * @return bool
     */
    public static function containsHtmlTags(string $toCheck): bool {
        return strpos($toCheck, '<') >= 0 || (bool)preg_match('/&#?[a-z0-9]{1,10};/i', $toCheck);
    }

    /**
     * HTML encode the contents of a code block so it doesn't get stripped out by the sanitizer.
     *
     * @param string $value
     *
     * @return string
     */
    private function encodeCodeBlocks(string $value): string {
        if (!$this->shouldEncodeCodeBlocks) {
            return $value;
        }
        return preg_replace_callback('`<code([^>]*)>(.+?)<\/code>`si', function ($matches) {
            $result = "<code{$matches[1]}>" .
                htmlspecialchars($matches[2]) .
                '</code>';
            return $result;
        }, $value);
    }
}
