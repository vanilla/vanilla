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
class ImageHtmlProcessor extends HtmlProcessor {

    const EMBED_IMAGE_XPATH = './/img[not(contains(@class, "emoji"))]';

    /**
     * @inheritdoc
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        return $document;
    }

    /**
     * Parse all images URLs from the document.
     *
     * @param HtmlDocument $document The document to parse.
     *
     * @return string[]
     */
    public function getImageURLs(HtmlDocument $document): array {
        $domImages = $document->queryXPath(self::EMBED_IMAGE_XPATH);

        /** @var string[] $headings */
        $imageUrls = [];

        /** @var \DOMElement $domImage */
        foreach ($domImages as $domImage) {
            $src = $domImage->getAttribute('src');
            if ($src) {
                $imageUrls[] = $src;
            }
        }

        return $imageUrls;
    }

    /**
     * @return string[]
     */
    public function getImages(HtmlDocument $document): array {
        $domImages = $document->queryXPath(self::EMBED_IMAGE_XPATH);

        /** @var array[] $headings */
        $images = [];

        /** @var \DOMElement $domImage */
        foreach ($domImages as $domImage) {
            $src = $domImage->getAttribute('src');
            if ($src) {
                $images[] = [
                    'url' => $src,
                    'alt' => $domImage->getAttribute('alt') ?: t('Untitled'),
                ];
            }
        }

        return $images;
    }
}
