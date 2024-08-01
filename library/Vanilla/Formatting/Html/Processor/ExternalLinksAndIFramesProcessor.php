<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use DOMElement;
use Gdn;
use Gdn_Request;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\Formatting\Html\DomUtils;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor for external links.
 */
class ExternalLinksAndIFramesProcessor extends HtmlProcessor
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
        $warnLeaving = $this->warnLeaving;

        $iframes = $document->getDom()->getElementsByTagName("iframe");
        if ($iframes->length > 0) {
            // Loop through the iframes and either convert to links or sanitize.
            /** @var DOMElement $iframe */
            foreach ($iframes as $iframe) {
                $src = $iframe->getAttribute("src");
                if (Gdn::config("Garden.Embeds.DisableUrlEmbeds") || (!isTrustedDomain($src) && $warnLeaving)) {
                    // replace with a link.
                    $node = $document->getDom()->createElement("a", $src);
                    $node->setAttribute("href", $src);
                    DomUtils::replaceEmbeds($document->getDom(), $node, ["iframe"]);
                } else {
                    // sanitize the iframe.
                    $iframe->setAttribute("sandbox", "allow-same-origin allow-scripts allow-forms");
                    $iframe->setAttribute("loading", "lazy");
                    $iframe->setAttribute("allowFullScreen", true);
                }
            }
        }

        // If we aren't redirecting external links, then we're done.
        if ($warnLeaving == false) {
            return $document;
        }

        $jsonComponentNodeTags = ["span", "div"];
        $targetedEmbedTypes = [LinkEmbed::TYPE];
        foreach ($jsonComponentNodeTags as $jsonComponentNodeTag) {
            $jsonComponentNodes = $document->getDom()->getElementsByTagName($jsonComponentNodeTag);
            if ($jsonComponentNodes->length > 0) {
                // Loop through spans/divs and add home/leaving to external links included within `data-embedjson` attributes.
                /** @var DOMElement $linkNode */
                foreach ($jsonComponentNodes as $jsonComponentNode) {
                    $spanNodeClasses = explode(" ", $jsonComponentNode->getAttribute("class"));
                    if (in_array("js-embed", $spanNodeClasses)) {
                        $embedJson = json_decode($jsonComponentNode->getAttribute("data-embedjson"));
                        $rawHref = $embedJson->url;
                        if (isExternalUrl($rawHref) && in_array($embedJson->embedType, $targetedEmbedTypes)) {
                            $leavingHref = $this->request->url(
                                "/home/leaving?" .
                                    http_build_query([
                                        "allowTrusted" => 1,
                                        "target" => $rawHref,
                                    ]),
                                true
                            );
                            $embedJson->url = $leavingHref;
                            $this->setAttribute($jsonComponentNode, "data-embedjson", json_encode($embedJson));
                        }
                    }
                }
            }
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
