<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots;

use Vanilla\Formatting\Quill\Formats\AbstractFormat;
use Vanilla\Formatting\Quill\Parser;

/**
 * Trait for rendering Formats.
 */
trait FormattableTextTrait {

    /** @var AbstractFormat[] */
    private $formats;

    /**
     * Parse out all of the formats in a set of operations.
     *
     * @param array $currentOp The current operation.
     * @param array $previousOp The next operation. Used to determine closing tags.
     * @param array $nextOp The previous operation. Used to determine opening tags.
     *
     * @throws \Garden\Container\ContainerException If the container can't be found.
     * @throws \Garden\Container\NotFoundException If the container can't find the Parser.
     */
    public function parseFormats(array $currentOp, array $previousOp = [], array $nextOp = []) {
        /** @var Parser $parser */
        $parser = \Gdn::getContainer()->get(Parser::class);
        $this->formats = $parser->getFormatsForOperations($currentOp, $previousOp, $nextOp);
    }

    /**
     * Render the opening tags for all of the formats.
     *
     * @return string
     */
    public function renderOpeningFormatTags(): string {
        $result = "";
        foreach ($this->formats as $format) {
            $result .= $format->renderOpeningTag();
        }
        return $result;
    }

    /**
     * Render the closing tags for all of the formats.
     *
     * @return string
     */
    public function renderClosingFormatTags(): string {
        $result = "";
        /** @var AbstractFormat $format */
        foreach (array_reverse($this->formats, true) as $format) {
            $result .= $format->renderClosingTag();
        }
        return $result;
    }
}
