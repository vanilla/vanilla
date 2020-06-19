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
     * @inheritDoc
     */
    public function processDocument(): HtmlDocument {
        return $this->document;
    }

    /**
     * @return string[]
     */
    public function getImageURLs(): array {
        $domImages = $this->queryXPath(self::EMBED_IMAGE_XPATH);

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
    public function getImages(): array {
        $domImages = $this->queryXPath(self::EMBED_IMAGE_XPATH);

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
