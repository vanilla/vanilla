<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use DOMNode;

/**
 * Class HtmlSnippet
 *
 * @package Vanilla\Utility
 */
class HtmlSnippet extends \DOMDocument {

    const CONTENT_ID = '__contentID';

    /**
     * Dom loadHmtl.
     *
     * @param string $source
     * @param int $options
     * @return bool|void
     */
    public function loadHTML($source, $options = 0) {
        $contentID = self::CONTENT_ID;
        // Use a big content prefix so we can force utf-8 parsing.
        $contentPrefix = <<<HTML
<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head>
<body><div id='$contentID'>
HTML;
        $contentSuffix = "</div></body></html>";
        parent::loadHTML($contentPrefix . $source . $contentSuffix);
    }

    /**
     * Dom saveXML.
     *
     * @param DOMNode|null $node
     * @param null $options
     * @return string
     */
    public function saveXML(DOMNode $node = null, $options = null) {
        if ($node === null) {
            $content = $this->getElementById(self::CONTENT_ID);
            $htmlBodyString = $this->saveXML($content, LIBXML_NOEMPTYTAG);
            $htmlBodyString = $this->getHtmlContent($htmlBodyString);
        } else {
            $htmlBodyString = parent::saveXML($node, $options);
            $htmlBodyString = $this->getHtmlContent($htmlBodyString);
        }
        return $htmlBodyString;
    }

    /**
     * Dom saveHtml.
     *
     * @param DOMNode|null $node
     * @param null $options
     * @return string
     */
    public function saveHtml(DOMNode $node = null, $options = null) {
        if ($node === null) {
            $content = $this->getElementById(self::CONTENT_ID);
            $htmlBodyString = $this->saveHtml($content, LIBXML_NOEMPTYTAG);
            $htmlBodyString = $this->getHtmlContent($htmlBodyString);
        } else {
            $htmlBodyString = parent::saveHtml($node);
            $htmlBodyString = $this->getHtmlContent($htmlBodyString);
        }
        return $htmlBodyString;
    }

    /**
     * Return the html content.
     *
     * @param string $htmlString
     */
    public function getHtmlContent(string $htmlString): string {
        $htmlString = preg_replace('#<div id="'.self::CONTENT_ID.'">#', '', $htmlString);
        $htmlString = preg_replace('#</div>#', '', $htmlString);
        return $htmlString;
    }
}
