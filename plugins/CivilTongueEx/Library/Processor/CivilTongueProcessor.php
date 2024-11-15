<?php

namespace CivilTongueEx\Library\Processor;

use CivilTongueEx\Library\ContentFilter;
use Vanilla\Formatting\Html\DomUtils;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\HtmlProcessor;
use Vanilla\Formatting\Html\Processor\HtmlProcessorTrait;
use Vanilla\Formatting\SanitizeInterface;

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
        $dom = $document->getDom();
        $content = $this->contentFilter->replace($document->getInnerHtml());
        DomUtils::setInnerHTML($dom->getElementsByTagName("body")[0], $content);

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
