<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\Quill\BlotGroup;
use Vanilla\Quill\Blots\HeadingBlot;
use Vanilla\Quill\Blots\TextBlot;

class BlockTest extends TestCase {
    public function testMakeEmptyBlock() {
        $block = BlotGroup::makeEmptyGroup();

        $this->assertEquals("<p><br></p>", $block->render(), "The empty block renders as a line break.");
    }

    public function testGetIndexForBlockType() {
        $block = new BlotGroup();
        $emptyBlot = new TextBlot([], [], []);
        $headingBlot = new HeadingBlot(["insert" => "H1",], [], [
            "attributes" => [
            "header" => 1,
        ]]);

        $block->pushBlot($emptyBlot);
        $block->pushBlot($emptyBlot);
        $block->pushBlot($headingBlot);
        $block->pushBlot($headingBlot);

        $this->assertTrue($block->getIndexForBlotOfType(HeadingBlot::class) === 2);
    }
}
