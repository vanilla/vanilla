<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use VanillaTests\Library\Vanilla\Formatting\FixtureRenderingTestCase;

/**
 * Unit tests for the Gdn_Format class.
 */
class GdnFormatTest extends FixtureRenderingTestCase {

    const FIXTURE_DIR = self::FIXTURE_ROOT . '/formats';

    /**
     * Test the BBCode HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideBBCode
     */
    public function testBBCodeToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture(self::FIXTURE_DIR . '/bbcode/html/' . $fixtureDir);
        $output = \Gdn_Format::bbCode($input);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * @return array
     */
    public function provideBBCode(): array {
        return $this->createFixtureDataProvider('/formats/bbcode/html');
    }

    /**
     * Test the markdown HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideMarkdown
     */
    public function testMarkdownToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture(self::FIXTURE_DIR . '/markdown/html/' . $fixtureDir);
        $output = \Gdn_Format::markdown($input);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * @return array
     */
    public function provideMarkdown(): array {
        return $this->createFixtureDataProvider('/formats/markdown/html');
    }

    /**
     * Test the text HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideText
     */
    public function testTextToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture(self::FIXTURE_DIR . '/text/html/' . $fixtureDir);
        $output = \Gdn_Format::text($input);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * @return array
     */
    public function provideText(): array {
        return $this->createFixtureDataProvider('/formats/text/html');
    }

    /**
     * Test the textEx HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideTextEx
     */
    public function testTextExToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture(self::FIXTURE_DIR . '/textex/html/' . $fixtureDir);
        $output = \Gdn_Format::textEx($input);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * @return array
     */
    public function provideTextEx(): array {
        return $this->createFixtureDataProvider('/formats/textex/html');
    }

    /**
     * Test the HTML HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideHtmlToHtml
     */
    public function testHtmlToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture(self::FIXTURE_DIR . '/html/html/' . $fixtureDir);
        $output = \Gdn_Format::html($input);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * @return array
     */
    public function provideHtmlToHtml(): array {
        return $this->createFixtureDataProvider('/formats/html/html');
    }

    /**
     * Test the wysiwyg HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideWysiwyg
     */
    public function testWysiwygToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture(self::FIXTURE_DIR . '/wysiwyg/html/' . $fixtureDir);
        $output = \Gdn_Format::wysiwyg($input);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * @return array
     */
    public function provideWysiwyg(): array {
        return $this->createFixtureDataProvider('/formats/wysiwyg/html');
    }
}
