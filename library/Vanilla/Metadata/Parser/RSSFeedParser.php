<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Metadata\Parser;

use DOMDocument;
use Garden\Schema\Schema;

/**
 * RSS Feed Parser.
 *
 * @package Vanilla\Metadata\Parser
 */
class RSSFeedParser extends AbstractXmlParser
{
    /**
     * RSS Feed schema.
     *
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return Schema::parse([
            "channel:o" => Schema::parse([
                "title:s",
                "link:s?",
                "description:s?",
                "image:o?" => Schema::parse(["url:s?", "title:s?", "link:s?"]),
            ]),
            "item:a" => Schema::parse([
                "title:s",
                "link:s",
                "description:s?",
                "category:s?",
                "pubDate:s?",
                "img:o?" => Schema::parse(["src:s", "title:s?", "alt:s?"]),
                "enclosure:o?" => Schema::parse(["length:s", "type:s", "url:s"]),
            ]),
        ]);
    }

    /**
     * Extract imag and enclosure attributes.
     *
     * @param string $fieldName
     * @param Schema|array $schema
     * @param \DOMElement $DOMElement
     * @return array|null
     */
    public function addDataToField(string $fieldName, $schema, \DOMElement $DOMElement)
    {
        if (!in_array($fieldName, ["img", "enclosure"])) {
            return null;
        }
        $schemaArray = is_array($schema) ? $schema : $schema->getSchemaArray();
        if (!isset($schemaArray["properties"])) {
            return [];
        }
        $fieldData = null;
        switch ($fieldName) {
            case "img":
                $fieldData = $this->getImgAttributes($DOMElement, $schemaArray);
                break;
            case "enclosure":
                $enclosureElement = $DOMElement->getElementsByTagName("enclosure")->item(0);
                $fieldData = $enclosureElement
                    ? $this->getAttributesFromElement($enclosureElement, $schemaArray)
                    : null;
                break;
        }

        return $fieldData;
    }

    /**
     * Get img tag attributes.
     *
     * @param \DOMElement $DOMImgElement
     * @param Schema|array $schema
     * @return array|null
     */
    private function getImgAttributes(\DOMElement $DOMImgElement, $schema): ?array
    {
        $schemaArray = is_array($schema) ? $schema : $schema->getSchemaArray();
        $content = $DOMImgElement->nodeValue;
        preg_match("/<img[^>]+>/i", trim($content), $result);
        $imageContent = count($result) > 0 ? $result[0] : null;
        if (!$imageContent) {
            return null;
        }
        $encoding = $DOMImgElement->ownerDocument->encoding ?? "UTF-8";
        $doc = new DOMDocument();
        $doc->loadHTML(sprintf('<?xml encoding="%s" ?>%s', $encoding, $imageContent));
        /** @var \DOMElement $imageElement */
        $imageElement = $doc->getElementsByTagName("img")->item(0);
        $attributes = $this->getAttributesFromElement($imageElement, $schemaArray);

        return $attributes;
    }
}
