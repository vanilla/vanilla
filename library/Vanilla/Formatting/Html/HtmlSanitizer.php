<?php

namespace Vanilla\Formatting\Html;

class HtmlSanitizer {

    /** @var \VanillaHtmlFormatter */
    private $htmlFilterer;

    /**
     *
     * @param \VanillaHtmlFormatter $htmlFilterer
     */
    public function __construct(\VanillaHtmlFormatter $htmlFilterer) {
        $this->htmlFilterer = $htmlFilterer;
    }

    public function filter(string $content, array $options = []): string {
        if (!self::containsHtmlTags($content)) {
            return $content;
        }

        $encodedCodeBlocks = $this->encodeCodeBlocks($content);
        return $this->htmlFilterer->format($encodedCodeBlocks, $options);
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
        return strpos($toCheck, '<') >= 0;
    }

    /**
     * HTML encode the contents of a code block so it doesn't get stripped out by the sanitizer.
     *
     * @param string $value
     *
     * @return string
     */
    private function encodeCodeBlocks(string $value): string {
        return preg_replace_callback('`<code([^>]*)>(.+?)<\/code>`si', function ($matches) {
            $result = "<code{$matches[1]}>" .
                htmlspecialchars($matches[2]) .
                '</code>';
            return $result;
        }, $value);
    }
}
