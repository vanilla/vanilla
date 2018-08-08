<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\BlotGroup;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;

class BlotGroupTest extends SharedBootstrapTestCase {

    public function testGetIndexForBlockType() {
        $block = new BlotGroup();
        $emptyBlot = new TextBlot([], [], []);
        $headingBlot = new HeadingBlot(["insert" => "H2",], [], [
            "attributes" => [
            "header" => 2,
        ]]);

        $block->pushBlot($emptyBlot);
        $block->pushBlot($emptyBlot);
        $block->pushBlot($headingBlot);
        $block->pushBlot($headingBlot);

        $this->assertTrue($block->getIndexForBlotOfType(HeadingBlot::class) === 2);
    }
}
