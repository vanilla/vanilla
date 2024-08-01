<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Gdn;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\ImageSrcSet\ImageSrcSetService;

/**
 * Processor of HMTL images.
 */
class ImageHtmlProcessor extends HtmlProcessor
{
    const EMBED_IMAGE_XPATH = './/img[not(contains(@class, "emoji"))]';

    /**
     * @inheritdoc
     */
    public function processDocument(HtmlDocument $document): HtmlDocument
    {
        $domImages = $document->queryXPath(self::EMBED_IMAGE_XPATH);

        $imageSrcSetService = Gdn::getContainer()->get(ImageSrcSetService::class);

        // For each images in the content, we attempt to build a srcset based on its original src.
        /** @var \DOMElement $domImage */
        foreach ($domImages as $domImage) {
            $isRichImage = $domImage->hasAttribute("data-display-size");

            if (!$isRichImage) {
                $height = $domImage->getAttribute("height");
                $width = $domImage->getAttribute("width");
                if ($width && !$height) {
                    $height = "auto";
                }
                if ($height && !$width) {
                    $width = "auto";
                }
                $styles = [];
                if ($height) {
                    if (is_numeric($height)) {
                        $height = $height . "px";
                    }
                    $styles[] = "height: " . htmlspecialchars($height);
                }
                if ($width) {
                    if (is_numeric($width)) {
                        $width = $width . "px";
                    }
                    $styles[] = "width: " . htmlspecialchars($width);
                }
                if (!empty($styles)) {
                    $domImage->setAttribute("style", implode("; ", $styles));
                }
            }

            $srcSetValues = [];
            $imageSrc = $domImage->getAttribute("src") ?: null;
            if (empty($imageSrc)) {
                continue;
            }

            $srcSetArray = $imageSrcSetService->getResizedSrcSet($imageSrc)->jsonSerialize();
            if (empty($srcSetArray)) {
                continue;
            }
            foreach ($srcSetArray as $srcSetWidth => $srcSetUrl) {
                // If for some reason we have an empty url, we do not bother adding it to the srcset.
                if (!empty($srcSetUrl)) {
                    $srcSetValues[] = $srcSetUrl . " " . $srcSetWidth . "w";
                }
            }

            // If we effectively built a srcset, we add it as the image's attribute.
            if (!empty($srcSetValues)) {
                // The images srcset needs a generic "sizeless" fallback image.
                $srcSetValues[] = $imageSrc;
                $domImage->setAttribute("srcset", implode(", ", $srcSetValues));
            }
        }

        return $document;
    }

    /**
     * Parse all images URLs from the document.
     *
     * @param HtmlDocument $document The document to parse.
     *
     * @return string[]
     */
    public function getImageURLs(HtmlDocument $document): array
    {
        $domImages = $document->queryXPath(self::EMBED_IMAGE_XPATH);

        /** @var string[] $headings */
        $imageUrls = [];

        /** @var \DOMElement $domImage */
        foreach ($domImages as $domImage) {
            $src = $domImage->getAttribute("src");
            if ($src) {
                $imageUrls[] = $src;
            }
        }

        return $imageUrls;
    }

    /**
     * @return string[]
     */
    public function getImages(HtmlDocument $document): array
    {
        $domImages = $document->queryXPath(self::EMBED_IMAGE_XPATH);

        /** @var array[] $headings */
        $images = [];

        /** @var \DOMElement $domImage */
        foreach ($domImages as $domImage) {
            $src = $domImage->getAttribute("src");

            if ($src) {
                $images[] = [
                    "url" => $src,
                    "alt" => $domImage->getAttribute("alt") ?: t("Untitled"),
                ];
            }
        }

        return $images;
    }
}
