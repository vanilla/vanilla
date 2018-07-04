<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

use Vanilla\Quill\Formats\AbstractFormat;

trait FormattableTextTrait {

    /** @var AbstractFormat[] */
    private $formats;

    public function parseFormats($currentOp, $previousOp, $nextOp) {
        /** @var Parser $parser */
        $parser = \Gdn::getContainer()->get(Parser::class);
        $this->formats = $parser->getFormatsForOperations($currentOp, $previousOp, $nextOp);
    }

    public function renderOpeningFormatTags(): string {
        $result = "";
        foreach ($this->formats as $format) {
            $result .= $format->renderOpeningTag();
        }
        return $result;
    }

    public function renderClosingFormatTags(): string {
        $result = "";
        foreach (array_reverse($this->formats, true) as $format) {
            $result .= $format->renderClosingTag();
        }
        return $result;
    }
}
