<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html;

use Garden\StaticCacheTranslationTrait;

/**
 * Class for converting HTML into plain text.
 */
class HtmlPlainTextConverter {

    use StaticCacheTranslationTrait;

    /** @var string; */
    private $addNewLinesAfterDiv = false;

    /**
     * Replaces opening html list tags with an asterisk and closing list tags with new lines.
     *
     * Accepts both encoded and decoded html strings.
     *
     * @param string $html An HTML-formatted string.
     * @return string Plain text.
     */
    public function convert(string $html): string {
        $result = $html;
        $result = $this->replaceHtmlElementsWithStrings(
            $result,
            [
                'Spoiler' => "(Spoiler)",
                'UserSpoiler' => "(Spoiler)",
                'Quote' => "(Quote)",
                'UserQuote' => "(Quote)",
            ],
            [
                'img' => "(Image)"
            ]
        );
        $result = $this->replaceListItems($result);
        $result = $this->replaceHtmlTags($result);
        $result = trim($result);
        return $result;
    }

    /**
     * @param string $addNewLinesAfterDiv
     */
    public function setAddNewLinesAfterDiv(string $addNewLinesAfterDiv): void {
        $this->addNewLinesAfterDiv = $addNewLinesAfterDiv;
    }

    /**
     * Check to see if a string has spoilers and replace them with an innocuous string.
     *
     * Good for displaying excerpts from discussions and without showing the spoiler text.
     *
     * @param string $html An HTML-formatted string.
     * @param array $classStringMapping A mapping of CSSClass => replacement string.
     * @param array $tagNameStringMapping A mapping of NodeName => replacement string.
     *
     * @return string Returns the html with spoilers removed.
     */
    protected function replaceHtmlElementsWithStrings(string $html, array $classStringMapping, array $tagNameStringMapping) {
        $contentID = 'contentID';

        // Use a big content prefix so we can force utf-8 parsing.
        $contentPrefix = <<<HTML
<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head>
<body><div id='$contentID'>
HTML;

        $contentSuffix = "</div></body></html>";
        $dom = new \DOMDocument();
        @$dom->loadHTML($contentPrefix . $html . $contentSuffix);

        foreach ($classStringMapping as $cssClass => $replacementString) {
            $xpath = new \DOMXPath($dom);
            $foundItems = $xpath->query(".//*[contains(@class, '$cssClass')]");

            /** @var \DOMNode $spoiler */
            foreach ($foundItems as $foundItem) {
                // Add the text content.
                /** @var \DOMElement $parent */
                $parent = $foundItem->parentNode;
                $textNode = $dom->createTextNode(self::t($replacementString));
                $breakNode = $dom->createElement('br');
                $parent->replaceChild($textNode, $foundItem);
                $parent->insertBefore($breakNode, $textNode->nextSibling);
            }
        }

        foreach ($tagNameStringMapping as $nodeName => $replacementString) {
            $foundItems = $dom->getElementsByTagName($nodeName);

            /** @var \DOMNode $spoiler */
            foreach ($foundItems as $foundItem) {
                // Add the text content.
                /** @var \DOMElement $parent */
                $parent = $foundItem->parentNode;
                $textNode = $dom->createTextNode(self::t($replacementString));
                $breakNode = $dom->createElement('br');
                $parent->replaceChild($textNode, $foundItem);
                $parent->insertBefore($breakNode, $textNode->nextSibling);
            }
        }

        $content = $dom->getElementById('contentID');
        $htmlBodyString = @$dom->saveXML($content, LIBXML_NOEMPTYTAG);
        return $htmlBodyString;
    }

    /**
     * Convert common tags in an HTML strings to plain text. You still need to sanitize your string!!!
     *
     * @param string $html An HTML-formatted string.
     * @param bool $collapse Treat a group of closing block tags as one when replacing with newlines.
     *
     * @return string An HTML-formatted strings with common tags replaced with plainText
     */
    private function replaceHtmlTags(string $html, bool $collapse = false): string {
        // Remove returns and then replace html return tags with returns.
        $result = str_replace(["\n", "\r"], '', $html);
        $result = preg_replace('`<br\s*/?>`', "\n", $result);

        // Fix lists.
        $result = $this->replaceListItems($result);

        $newLineBlocks = [
            'table',
            'dl',
            'pre',
            'blockquote',
            'address',
            'p',
            'h[1-6]',
            'section',
            'article',
            'aside',
            'hgroup',
            'header',
            'footer',
            'nav',
            'figure',
            'figcaption',
            'details',
            'menu',
            'summary',
        ];

        if ($this->addNewLinesAfterDiv) {
            $result = preg_replace("`</(?:div)>`", "\n", $result);
        }

        $endTagRegex = '</(?:' . implode('|', $newLineBlocks) . ')>';
        if ($collapse) {
            $endTagRegex = "((\s+)?{$endTagRegex})+";
        }

        // Put back the code blocks.
        $result = preg_replace("`{$endTagRegex}`", "\n\n", $result);

        // TODO: Fix hard returns within pre blocks.

        // Cleanup the starting tags and any other stray tags.
        // We have to be careful of our code blocks here, so we'll replace them with some well known value.
        // THIS IS NOT A SECURITY MEASURE. YOU STILL NEED TO HTML ESCAPE CONTENT WHERE NECESSARY.
        $result = strip_tags($result);
        $result = htmlspecialchars_decode($result);
        return $result;
    }

    /**
     * Replaces opening html list tags with an asterisk and closing list tags with new lines.
     *
     * Accepts both encoded and decoded html strings.
     *
     * @param string $html An HTML-formatted string.
     * @return string Returns the HTML with all list items removed.
     */
    private function replaceListItems(string $html): string {
        // Strip the wrapping tags.
        $html = str_replace(['<ul>', '<ol>'], '', $html);
        $html = str_replace(['</ul>', '</ol>'], '<br>', $html);

        // Replace starting tags.
        $html = str_replace(['<li>', '&lt;li&gt;'], '* ', $html);

        $regexes = [
            '/(<\/?(?:li|ul|ol)([^>]+)?>)/', // UTF-8 encoded
            '/(&lt;\/?(?:li|ul|ol)([^&]+)?&gt;)/' // HtmlEncoded
        ];

        // Replace closing tags.
        $html = preg_replace($regexes, "<br>", $html);
        return $html;
    }
}
