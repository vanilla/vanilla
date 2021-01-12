<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html;

use Vanilla\Formatting\Html\Processor\HtmlProcessor;
use Vanilla\Formatting\Html\Processor\HtmlProcessorTrait;

/**
 * Class for parsing and modifying HTML.
 */
class HtmlDocument {

    use HtmlProcessorTrait;

    /** @var \DOMDocument */
    private $dom;

    /** @var bool */
    private $wrap;

    /**
     * Constructor.
     *
     * @param string $innerHtml HTML to construct the DOM with.
     * @param bool $wrap Whether or not to wrap in our own fragment prefix/suffix.
     */
    public function __construct(string $innerHtml, bool $wrap = true) {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->wrap = $wrap;

        // DomDocument will automatically add html, head and body wrapper if we don't.
        // We add our own to ensure consistency.
        if ($wrap) {
            $innerHtml = $this->getDocumentPrefix() . $innerHtml . $this->getDocumentSuffix();
        }
        @$this->dom->loadHTML($innerHtml, LIBXML_NOBLANKS);
    }

    /**
     * @return HtmlDocument
     */
    protected function getDocument(): HtmlDocument {
        return $this;
    }

    /**
     * Query the DOM with some xpath.
     *
     * @param string $xpathQuery
     * @see https://devhints.io/xpath For a cheatsheet.
     *
     * @return \DOMNodeList
     */
    public function queryXPath(string $xpathQuery) {
        $xpath = new \DOMXPath($this->getDom());
        return $xpath->query($xpathQuery) ?: new \DOMNodeList();
    }

    /**
     * Get the document.
     *
     * @return \DOMDocument
     */
    public function getDom(): \DOMDocument {
        return $this->dom;
    }

    /**
     * Return the inner HTML content of the document.
     * We grab everything inside the document body.
     *
     * @return string
     */
    public function getInnerHtml(): string {
        if ($this->wrap) {
            $content = $this->dom->getElementsByTagName('body');
            $result = @$this->dom->saveXML($content[0], LIBXML_NOEMPTYTAG);

            // The DOM Document added starting body and ending tags. We need to remove them.
            $result = preg_replace('/^<body>/', '', $result);
            $result = preg_replace('/<\/body>$/', '', $result);
        } else {
            $result = @$this->dom->saveXML(null, LIBXML_NOEMPTYTAG);
        }
        // saveXML adds closing <br> tags, which breaks formatting.
        $result = preg_replace('/<\/br>/', '', $result);
        return $result;
    }

    /**
     * Get the opening tag of the document.
     *
     * @return string
     */
    private function getDocumentPrefix() {
        return <<<HTML
    <html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head>
    <body>
HTML;
    }

    /**
     * Get the closing tag of the document.
     * @return string
     */
    private function getDocumentSuffix() {
        return "</body></html>";
    }
}
