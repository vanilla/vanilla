<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Formatting\Attachment;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor of HMTL attachments.
 */
class AttachmentHtmlProcessor extends HtmlProcessor {

    /**
     * @inheritdoc
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        return $document;
    }

    /**
     * Get all the attachments in the document.
     *
     * @param HtmlDocument $document The document to parse.
     *
     * @return Attachment[]
     */
    public function getAttachments(HtmlDocument $document): array {
        $domLinks = $document->queryXPath('.//a[@download]');

        /** @var Attachment[] $attachemnts */
        $attachemnts = [];

        /** @var \DOMElement $domLink */
        foreach ($domLinks as $domLink) {
            $href = $domLink->getAttribute('href');
            $name = $domLink->textContent;

            if (!$href || !$name) {
                continue;
            }

            $attachment = Attachment::fromArray([
                'name' => $name,
                'url' => $href,
                'size' => 0,
                'type' => "unknown",
                'mediaID' => -1,
                'dateInserted' => null,
            ]);
            $attachemnts[] = $attachment;
        }

        return $attachemnts;
    }
}
