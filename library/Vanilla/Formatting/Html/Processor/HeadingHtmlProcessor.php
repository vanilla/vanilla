<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor of HMTL headings.
 */
class HeadingHtmlProcessor extends HtmlProcessor {

    /**
     * @inheritdoc
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        $this->getHeadings($document, true);
        return $document;
    }

    /**
     * Get all the headings in the document.
     *
     * @param HtmlDocument $document The document to parse.
     * @param bool $applyToDom Whether or not to apply the heading ids into the dom.
     *
     * @return Heading[]
     */
    public function getHeadings(HtmlDocument $document, bool $applyToDom = false): array {
        $domHeadings = $document->queryXPath('.//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]');

        /** @var Heading[] $headings */
        $headings = [];

        // Mapping of $key => $usageCount.
        $slugKeyCache = [];

        /** @var \DOMElement $domHeading */
        foreach ($domHeadings as $domHeading) {
            $level = (int) str_replace('h', '', $domHeading->tagName);

            $text = $domHeading->textContent;
            if ($text === "") {
                // Ignore empty slugs.
                continue;
            }
            $slug = slugify($text);

            $count = $slugKeyCache[$slug] ?? 0;
            $slugKeyCache[$slug] = $count + 1;
            if ($count > 0) {
                $slug .= '-' . $count;
            }

            if ($applyToDom) {
                $domHeading->setAttribute('data-id', $slug);
            }

            $headings[] = new Heading(
                $domHeading->textContent,
                $level,
                $slug
            );
        }

        return $headings;
    }
}
