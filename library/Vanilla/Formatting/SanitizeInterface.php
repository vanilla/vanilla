<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting;

use Vanilla\Formatting\Html\HtmlDocument;

interface SanitizeInterface
{
    /**
     * Sanitize Html document.
     *
     * @param HtmlDocument $document The HTML Document to sanitize.
     * @return HtmlDocument The sanitized HTML Document.
     */
    public function sanitizeHtml(HtmlDocument $document): HtmlDocument;

    public function sanitizeText(string $text): string;
}
