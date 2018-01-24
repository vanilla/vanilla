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

    /** @var QuillRenderer */
    private $renderer;

    public function __construct(?string $name = null, array $data = [], string $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->renderer = new QuillRenderer();
    }

    private function renderFixtureDirectory($dirname) {
        $fixturePath = realpath("../../fixtures/editor-rendering/".$dirname);

        $input = file_get_contents(realpath($fixturePath . "/input.json"));
        $expectedOutput = trim(file_get_contents(realpath($fixturePath . "/output.html")));

        $output = $this->renderer->renderDelta($input);
        $this->assertEquals($expectedOutput, $output);
    }

    public function testRenderParagraphs() {
        $this->renderFixtureDirectory("paragraphs");
    }

    public function testRenderHeadings() {
        $this->renderFixtureDirectory("headings");
    }

    public function testRenderInline() {
        $this->renderFixtureDirectory("inline-formatting");
    }

    public function testRenderLists() {
        $this->renderFixtureDirectory("lists");
    }

    public function testRenderEverything() {
        $this->renderFixtureDirectory("everything");
    }
}
