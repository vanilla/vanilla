<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use DOMElement;
use Gdn_Request;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor for external links.
 */
class ExternalLinksProcessor extends HtmlProcessor {

    /** @var Gdn_Request */
    private $request;

    /**
     * Setup the class.
     *
     * @param Gdn_Request $request
     */
    public function __construct(Gdn_Request $request) {
        $this->request = $request;
    }

    /**
     * Return processor type.
     *
     * @return string
     */
    public function getProcessorType(): string {
        return self::TYPE_DYNAMIC;
    }

    /**
     * Setter to enable/disable the Ex
     *
     * @param bool $value
     */
    public function setEnabled(bool $value = true) {
        $this->enabled = $value;
    }

    /**
     * Loop through the links and add home/leaving to external links.
     *
     * @param HtmlDocument $document
     * @return HtmlDocument
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        $linkNodes = $document->getDom()->getElementsByTagName('a');

        if ($linkNodes->length > 0) {
            // Loop through the links and add home/leaving to external links.
            /** @var DOMElement $linkNode */
            foreach ($linkNodes as $linkNode) {
                $rawHref = $linkNode->getAttribute('href');
                if (isExternalUrl($rawHref)) {
                    $leavingHref = $this->request->url("/home/leaving?" . http_build_query([
                        "allowTrusted" => 1,
                        "target" => $rawHref,
                    ]));
                    $this->setAttribute($linkNode, 'href', $leavingHref);
                }
            }
        }

        return $document;
    }
}
