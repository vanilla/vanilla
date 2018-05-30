<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Metadata\Parser;

use DOMDocument;

interface Parser {
    /**
     * Parse a document for metadata.
     *
     * @param DOMDocument $document
     * @return array
     */
    public function parse(DOMDocument $document): array;
}
