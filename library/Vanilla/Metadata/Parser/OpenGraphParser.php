<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Metadata\Parser;

use DOMDocument;

class OpenGraphParser implements Parser {

    /**
     * @inheritdoc
     */
    public function parse(DOMDocument $document): array {
        /** @var \DOMNodeList $metaTags */
        $metaTags = $document->getElementsByTagName('meta');
        $images = [];
        $result = [];

        /** @var \DOMElement $tag */
        foreach ($metaTags as $tag) {
            if ($tag->hasAttribute('property') === false) {
                continue;
            } elseif (substr($tag->getAttribute('property'), 0, 3) !== 'og:') {
                continue;
            }

            $property = $tag->getAttribute('property');
            $content = $tag->getAttribute('content');

            switch ($property) {
                case 'og:title':
                    $result['Title'] = $content;
                    break;
                case 'og:description':
                    $result['Description'] = $content;
                    break;
                case 'og:image':
                    // Only allow valid URLs.
                    if (filter_var($content, FILTER_VALIDATE_URL) === false) {
                        continue 2;
                    }
                    $images[] = $content;
                    break;
            }
        }

        if (count($images)) {
            $result['Images'] = $images;
        }

        return $result;
    }
}
