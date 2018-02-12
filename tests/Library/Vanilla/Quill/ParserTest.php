<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\Quill\Parser;
use Vanilla\Quill\Blots;


class ParserTest extends TestCase {
    /**
     * @dataProvider directoryProvider
     */
    public function testRenderDelta($dirname) {
        $fixturePath = realpath(__DIR__."/../../../fixtures/editor-rendering/".$dirname);

        $input = file_get_contents($fixturePath . "/input.json");
        $expectedOutput = trim(file_get_contents($fixturePath . "/output.html"));

        $blots = [
            Blots\TextBlot::class,
        ];

        $json = \json_decode($input, true);

        $parser = new Parser($json, $blots);

        $output = $parser->render();
        $this->assertEquals($expectedOutput, $output);
    }

    public function directoryProvider() {
        return [
            ["inline-formatting"],
            ["paragraphs"],
            ["headings"],
            ["lists"],
            ["everything"],
        ];
    }
}
