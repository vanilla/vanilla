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
 * Processor of HMTL headings.
 */
class AttachmentHtmlProcessor extends HtmlProcessor {

    /**
     * @inheritDoc
     */
    public function processDocument(): HtmlDocument {
        return $this->document;
    }

    /**
     * Get all the headings in the document.
     *
     * @param bool $applyToDom Whether or not to apply the heading ids into the dom.
     *
     * @return Heading[]
     */
    public function getAttachments(bool $applyToDom = false): array {
        $domLinks = $this->queryXPath('.//a[@download]');

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
