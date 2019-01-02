<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\BlotGroup;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;

class BlotGroupTest extends SharedBootstrapTestCase {

    public function testGetIndexForBlockType() {
        $block = new BlotGroup();
        $emptyBlot = new TextBlot([], [], []);
        $headingBlot = new HeadingTerminatorBlot(["insert" => "H2",], [], [
            "attributes" => [
            "header" => 2,
        ]]);

        $block->pushBlots([$emptyBlot]);
        $block->pushBlots([$emptyBlot]);
        $block->pushBlots([$headingBlot]);
        $block->pushBlots([$headingBlot]);

        $this->assertTrue($block->getIndexForBlotOfType(HeadingTerminatorBlot::class) === 2);
    }
}
