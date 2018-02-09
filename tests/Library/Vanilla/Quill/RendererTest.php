<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\Quill\Renderer;

class RendererTest extends TestCase {

    /** @var Renderer */
    private $renderer;

    public function __construct(string $name = null, array $data = [], string $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->renderer = new Renderer();
    }

    /**
     * @dataProvider directoryProvider
     */
    public function testRenderDelta($dirname) {
        $fixturePath = realpath(__DIR__."/../../../fixtures/editor-rendering/".$dirname);

        $input = file_get_contents($fixturePath . "/input.json");
        $expectedOutput = trim(file_get_contents($fixturePath . "/output.html"));

        $output = $this->renderer->renderDelta($input);
        $this->assertEquals($expectedOutput, $output);
    }

    public function directoryProvider() {
        return [
            ["paragraphs"],
            ["headings"],
            ["lists"],
            ["everything"],
        ];
    }
}
