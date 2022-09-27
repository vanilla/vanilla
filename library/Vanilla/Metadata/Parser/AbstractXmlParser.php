<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Metadata\Parser;

use DOMDocument;
use Garden\Schema\Schema;

/**
 * Abstract Xml Parser.
 *
 * @package Vanilla\Metadata\Parser
 */
abstract class AbstractXmlParser implements Parser
{
    /**
     * Parse a xml content.
     *
     * @param DOMDocument $document
     * @return array
     */
    public function parse(DOMDocument $document): array
    {
        $results = [];
        $schemaArray = $this->getSchema()->getSchemaArray();
        foreach ($schemaArray["properties"] as $fieldName => $property) {
            $type = $property["type"] ?? null;
            $elements = $document->getElementsByTagName($fieldName);

            /** @var \DOMElement $element */
            foreach ($elements as $element) {
                if ($type === "object") {
                    $results[$fieldName] = $this->getXMLData($property, $element);
                } elseif ($type == "array") {
                    $results[$fieldName][] = $this->getXMLData($property["items"], $element);
                } else {
                    $results[$fieldName] = trim($element->nodeValue);
                }
            }
        }
        $results = $this->getSchema()->validate($results);

        return $results;
    }

    /**
     * Get the value from an element.
     *
     * @param Schema|array $schema
     * @param \DOMElement $domElement
     * @return array
     */
    private function getXMLData($schema, \DOMElement $domElement): array
    {
        $schemaArray = is_array($schema) ? $schema : $schema->getSchemaArray();
        if (!isset($schemaArray["properties"])) {
            return [];
        }
        $data = [];
        foreach ($schemaArray["properties"] as $fieldName => $property) {
            $type = $property["type"] ?? null;
            if ($type === "array") {
                $data[$fieldName][] = $this->getXMLData($property["items"], $domElement);
            } elseif ($type === "object") {
                $childDOMElement = $domElement->getElementsByTagName($fieldName)->item(0);
                if ($childDOMElement) {
                    $data[$fieldName] = $this->getXMLData($property, $childDOMElement);
                }
            } else {
                /** @var \DOMNode|null $node */
                $node = $domElement->getElementsByTagName($fieldName)->item(0);
                if ($node) {
                    $data[$fieldName] = trim($node->nodeValue);
                }
            }

            $moreData = $this->addDataToField($fieldName, $property, $domElement);
            if ($moreData) {
                $mergeData = isset($data[$fieldName]) && is_array($data[$fieldName]);
                $data[$fieldName] = $mergeData ? array_merge($data[$fieldName], $moreData) : $moreData;
            }
        }

        return $data;
    }

    /**
     * Get parser schema.
     *
     * @return Schema
     */
    abstract public function getSchema(): Schema;

    /**
     * Provide a way to add other data to an entry.
     *
     * @param string $fieldName
     * @param Schema|array $schema
     * @param \DOMElement $DOMElement
     * @return mixed|null
     */
    abstract public function addDataToField(string $fieldName, $schema, \DOMElement $DOMElement);

    /**
     * Get attributes value.
     *
     * @param \DOMElement $DOMElement
     * @param Schema|array $schema
     * @return array
     */
    protected function getAttributesFromElement(\DOMElement $DOMElement, $schema): array
    {
        $schemaArray = is_array($schema) ? $schema : $schema->getSchemaArray();
        $attributes = [];
        foreach ($schemaArray["properties"] as $attributeName => $property) {
            $attributes[$attributeName] = $DOMElement->getAttribute($attributeName);
        }

        return $attributes;
    }
}
