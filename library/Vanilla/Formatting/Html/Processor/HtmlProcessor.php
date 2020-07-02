<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor an HtmlDocument.
 */
abstract class HtmlProcessor {

    /** @var HtmlDocument */
    protected $document;

    /**
     * Constructor.
     *
     * @param HtmlDocument $document
     */
    public function __construct(HtmlDocument $document) {
        $this->document = $document;
    }

    /**
     * @param HtmlDocument $document
     */
    public function setDocument(HtmlDocument $document) {
        $this->document = $document;
    }

    /**
     * Process the HTML document in some way.
     *
     * @return HtmlDocument
     */
    abstract public function processDocument(): HtmlDocument;

    ///
    /// Some Utilities
    ///

    /**
     * Get Attribute from dom node
     *
     * @param string $attr The attribute you want
     * @param \DOMElement $domNode The dom node
     *
     * @return array
     */
    protected function getAttrData(string $attr, \DOMElement $domNode) {
        // Empty array to hold all classes to return
        //Loop through each tag in the dom and add it's attribute data to the array
        $attrData = [];
        if (empty($domNode->getAttribute($attr)) === false) {
            $attrData = explode(" ", $domNode->getAttribute($attr));
        } else {
            array_push($attrData, "");
        }
        //Return the array of attribute data
        return array_unique($attrData);
    }

    /**
     * Get dom node classes
     *
     * @param \DOMElement $domElement the dom element
     * @return array array
     */
    protected function getClasses($domElement) {
        return self::getAttrData('class', $domElement);
    }

    /**
     * Check if class exists in class array
     *
     * @param string[] $classes
     * @param string $target
     * @return bool
     */
    protected function hasClass($classes, $target) {
        foreach ($classes as $c) {
            if ($c === $target) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set attribute on dom node
     *
     * @param \DOMElement $domNode
     * @param string $key
     * @param string $value
     */
    protected function setAttribute(\DOMElement $domNode, $key, $value) {
        $domNode->setAttribute($key, $value);
    }

    /**
     * Append class to dom node.
     *
     * @param \DOMElement $domNode
     * @param string $class
     */
    protected function appendClass(\DOMElement &$domNode, $class) {
        if (empty($domNode->getAttribute("class"))) {
            $domNode->setAttribute("class", $class);
        } else {
            $domNode->setAttribute("class", $domNode->getAttribute("class") . " " . $class);
        }
    }

    /**
     * Query the DOM with some xpath.
     *
     * @param string $xpathQuery
     * @see https://devhints.io/xpath For a cheatsheet.
     *
     * @return \DOMNodeList
     */
    protected function queryXPath(string $xpathQuery) {
        $xpath = new \DOMXPath($this->document->getDom());
        return $xpath->query($xpathQuery) ?: new \DOMNodeList();
    }
}
