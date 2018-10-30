<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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

    public function setUp() {
        parent::setUp();
        BlotGroup::resetIDs();
    }

    /**
     * Test BlotGroup::makeUniqueIDFromText()
     *
     * This does not use a dataProvider because of the reset after every test.
     */
    public function testMakeUniqueIDFromText() {
        // Test basic incrementing and usage.
        $ios = [
            ["Title number one", "title-number-one"],
            ["Title number one", "title-number-one-1"],
            ["Title nUmber- OnE", "title-number-one-2"],
            ["Title \$number \$\$\$...one", "title-number-one-3"],
            ["123 456", "123-456"],
            ["Some        Words", "some-words"],
            ["", ""],
            [" ", "-1"],
            ["  ", "-2"],
        ];

        foreach ($ios as $io) {
            list($input, $output) = $io;
            $this->assertEquals($output, BlotGroup::makeUniqueIDFromText($input));
        }
    }

    public function testReset() {
        $this->assertIncrementing();
        BlotGroup::resetIDs();
        $this->assertIncrementing();
    }

    /**
     * Assert that basic incrementation works, starting from "0"
     */
    private function assertIncrementing() {
        $ios = [
            ["One", "one"],
            ["One", "one-1"],
        ];

        foreach ($ios as $io) {
            list($input, $output) = $io;
            $this->assertEquals($output, BlotGroup::makeUniqueIDFromText($input));
        }
    }
}
