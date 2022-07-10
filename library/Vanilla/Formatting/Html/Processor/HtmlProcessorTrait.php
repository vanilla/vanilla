<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Symfony\Component\CssSelector\CssSelectorConverter;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Trait containing values for a
 */
trait HtmlProcessorTrait {

    /**
     * Get Attribute from dom node
     *
     * @param string $attr The attribute you want
     * @param \DOMElement $domNode The dom node
     *
     * @return array
     */
    public function getAttrData(string $attr, \DOMElement $domNode) {
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
     * Extract outer HTML from a domnode.
     *
     * @param \DOMElement $element
     * @return string
     */
    public function getOuterHtml(\DOMElement $element): string {
        return $this->getDom()->saveHTML($element);
    }

    /**
     * Get dom node classes
     *
     * @param \DOMElement $domElement the dom element
     * @return array array
     */
    public function getClasses($domElement) {
        return self::getAttrData('class', $domElement);
    }

    /**
     * Check if class exists in class array
     *
     * @param string[] $classes
     * @param string $target
     * @return bool
     */
    public function hasClass($classes, $target) {
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
    public function setAttribute(\DOMElement $domNode, $key, $value) {
        $domNode->setAttribute($key, $value);
    }

    /**
     * Append class to dom node.
     *
     * @param \DOMElement $domNode
     * @param string $class
     */
    public function appendClass(\DOMElement &$domNode, $class) {
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
    public function queryXPath(string $xpathQuery): \DOMNodeList {
        $xpath = new \DOMXPath($this->getDom());
        return $xpath->query($xpathQuery) ?: new \DOMNodeList();
    }

    /**
     * Query the DOM with some CSS selector.
     *
     * @param string $cssQuery
     * @see https://devhints.io/xpath For a cheatsheet.
     *
     * @return \DOMNodeList
     */
    public function queryCssSelector(string $cssQuery): \DOMNodeList {
        $converter = new CssSelectorConverter();
        $xpath = $converter->toXPath($cssQuery);
        return $this->queryXPath($xpath);
    }
}
