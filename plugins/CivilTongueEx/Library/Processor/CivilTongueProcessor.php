<?php

namespace CivilTongueEx\Library\Processor;

use CivilTongueEx\Library\ContentFilter;
use Gdn;
use Vanilla\Formatting\Html\DomUtils;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\HtmlProcessorTrait;
use Vanilla\Formatting\SanitizeInterface;
use Vanilla\Utility\ModelUtils;

class CivilTongueProcessor implements SanitizeInterface
{
    use HtmlProcessorTrait;
    private ContentFilter $contentFilter;

    /**
     * set up the class
     * @param \Gdn_Request $request
     */
    public function __construct(ContentFilter $contentFilter)
    {
        $this->contentFilter = $contentFilter;
    }

    /**
     * Process the HTML document to clean for civil tongue.
     *
     * @param HtmlDocument $document
     * @return HtmlDocument
     */
    public function sanitizeHtml(HtmlDocument $document): HtmlDocument
    {
        $meta = Gdn::request()->getMetaArray();
        if (isset($meta["expand"]) && ModelUtils::isExpandOption("crawl", $meta["expand"])) {
            return $document;
        }

        $rootElement = $document->getRoot();
        $content = $document->getInnerHtml();
        $content = $this->contentFilter->replace($content, true);
        // We need proper HTML, to be passed along here
        DomUtils::setInnerHTML($rootElement, $content);
        return $document;
    }

    /**
     * Sanitize text for civil tongue.
     *
     * @param string $text
     * @return string
     */
    public function sanitizeText(string $text): string
    {
        return $this->contentFilter->replace($text);
    }
}
