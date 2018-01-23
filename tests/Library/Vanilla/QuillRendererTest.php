<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Vanilla\QuillRenderer;

class QuillRendererTest extends TestCase {
    public function testRenderDelta() {
        $fixturePath = realpath("../../fixtures/editor-rendering");
        $testDirectories = array_reverse(array_diff(scandir($fixturePath), ['..', '.']));
        $renderer = new QuillRenderer();

        foreach($testDirectories as $dir) {
            $input = file_get_contents(realpath($fixturePath . "/" . $dir . "/input.json"));
            $expectedOutput = trim(file_get_contents(realpath($fixturePath . "/" . $dir . "/output.html")));

            $output = $renderer->renderDelta($input);
            $this->assertEquals($expectedOutput, $output);
        }
    }
}
