<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\Formats;
use Vanilla\Quill\Block;

class TextBlot extends AbstractBlot {

    /** @var array[] */
    private $openingTags = [];

    /** @var array[] */
    private $closingTags = [];

    /**
     * The inline formats to use.
     */
    const FORMATS = [
        Formats\Link::class,
        Formats\Bold::class,
        Formats\Italic::class,
        Formats\Code::class,
        Formats\Strike::class,
    ];

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
        $this->content = val("insert", $this->currentOperation);
    }


    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        $matches = false;

        foreach ($operations as $op) {
            if (\is_string(val("insert", $op))) {
                $matches = true;
                break;
            }
        }

        return $matches;
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        foreach(self::FORMATS as $format) {
            if ($format::matches([$this->currentOperation])) {
                /** @var Formats\AbstractFormat $formatInstance */
                $formatInstance = new $format($this->currentOperation, $this->previousOperation, $this->nextOperation);
                $this->openingTags[] = $formatInstance->getOpeningTag();
                $this->closingTags[] = $formatInstance->getClosingTag();
            }
        }

        $this->closingTags = \array_reverse($this->closingTags);

        $result = "";
        foreach($this->openingTags as $tag) {
            $result .= self::renderOpeningTag($tag);
        }

        $result .= $this->createLineBreaks($this->content);
        foreach($this->closingTags as $tag) {
            $result .= self::renderClosingTag($tag);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentBlock(Block $block): bool {
        return false;
    }

    /**
     * Render the opening tags for the current blot.
     *
     * @param array $tag - The tag to render.
     * - string $tag
     * - array $attributes
     *
     * @return string;
     */
    private static function renderOpeningTag(array $tag): string {
        $tagName = val("tag", $tag);

        if (!$tagName) {
            return "";
        }

        $result = "<".$tagName;

        /** @var array $attributes */
        $attributes = val("attributes", $tag);
        if ($attributes) {
            foreach ($attributes as $attrKey => $attr) {
                $result .= " $attrKey=\"$attr\"";
            }
        }

        $result .= ">";
        return $result;
    }

    /**
     * Render the closing tags for the current blot.
     *
     * @param array $tag - The tag to render.
     * - string $tag
     * - array $attributes
     *
     * @return string;
     */
    private static function renderClosingTag(array $tag): string {
        $closingTag = val("tag", $tag);

        if (!$closingTag) {
            return "";
        }

        return "</".$closingTag.">";
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function createLineBreaks(string $input): string {
        // Replace any newlines with breaks
        $singleNewLineReplacement = "</p><p>";
        return \preg_replace("/\\n/", $singleNewLineReplacement, $input);
    }
}
