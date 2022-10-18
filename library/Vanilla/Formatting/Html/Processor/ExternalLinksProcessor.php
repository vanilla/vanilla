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
class ExternalLinksProcessor extends HtmlProcessor
{
    /** @var Gdn_Request */
    private $request;

    /** @var bool */
    private $warnLeaving = true;

    /**
     * Setup the class.
     *
     * @param Gdn_Request $request
     */
    public function __construct(Gdn_Request $request)
    {
        $this->request = $request;
    }

    /**
     * Return processor type.
     *
     * @return string
     */
    public function getProcessorType(): string
    {
        return self::TYPE_DYNAMIC;
    }

    /**
     * Should external links be redirected through the leaving page?
     *
     * @param bool $warnLeaving
     */
    public function setWarnLeaving(bool $warnLeaving): void
    {
        $this->warnLeaving = $warnLeaving;
    }

    /**
     * Loop through the links and add home/leaving to external links.
     *
     * @param HtmlDocument $document
     * @return HtmlDocument
     */
    public function processDocument(HtmlDocument $document): HtmlDocument
    {
        // Currently, this processor only redirects external links to the leaving page. If we aren't doing that, bail.
        if ($this->warnLeaving === false) {
            return $document;
        }

        $linkNodes = $document->getDom()->getElementsByTagName("a");

        if ($linkNodes->length > 0) {
            // Loop through the links and add home/leaving to external links.
            /** @var DOMElement $linkNode */
            foreach ($linkNodes as $linkNode) {
                $rawHref = $linkNode->getAttribute("href");
                if (isExternalUrl($rawHref)) {
                    $leavingHref = $this->request->url(
                        "/home/leaving?" .
                            http_build_query([
                                "allowTrusted" => 1,
                                "target" => $rawHref,
                            ]),
                        true
                    );
                    $this->setAttribute($linkNode, "href", $leavingHref);
                }
            }
        }

        return $document;
    }
}
