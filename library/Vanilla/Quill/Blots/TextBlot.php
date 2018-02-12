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

    private $openingTags = [];
    private $closingTags = [];
    protected $content = "";

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
        $this->content = $this->currentOperation["insert"];
    }


    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return true;
    }

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

        // Trim trailing new lines.
        $strippedContent = \rtrim($this->content, "\n");

        $result .= $strippedContent;
        foreach($this->closingTags as $tag) {
            $result .= self::renderClosingTag($tag);
        }

        return $result;
    }

    public function shouldClearCurrentBlock(Block $block): bool {
        return false;
    }

    private static function renderOpeningTag(array $tag) {
        $tagName = val("tag", $tag);

        if (!$tagName) {
            return "";
        }

        $result = "<".$tagName;

        if (\array_key_exists("attributes", $tag)) {
            foreach ($tag["attributes"] as $attrKey => $attr) {
                $result .= " $attrKey=\"$attr\"";
            }
        }

        $result .= ">";
        return $result;
    }

    private static function renderClosingTag(array $tag) {
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
}
