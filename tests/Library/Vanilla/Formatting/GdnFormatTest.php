<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Formats\RichFormat;
use VanillaTests\AssertsStaticCallsTrait;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Unit tests for the Gdn_Format class.
 */
class GdnFormatTest extends SharedBootstrapTestCase {

    use AssertsStaticCallsTrait;
    use AssertsFixtureRenderingTrait;

    /**
     * Test the BBCode HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideBBCode
     */
    public function testBBCodeToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture($this->getFixtureDir() . '/bbcode/html/' . $fixtureDir);
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
        list($input, $expectedOutput) = $this->getFixture($this->getFixtureDir() . '/markdown/html/' . $fixtureDir);
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
        list($input, $expectedOutput) = $this->getFixture($this->getFixtureDir() . '/text/html/' . $fixtureDir);
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
        list($input, $expectedOutput) = $this->getFixture($this->getFixtureDir() . '/textex/html/' . $fixtureDir);
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
        list($input, $expectedOutput) = $this->getFixture($this->getFixtureDir() . '/html/html/' . $fixtureDir);
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
     * Test results of Gdn_Format::excerpt with rich-formatted content.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     * @dataProvider provideRichExcerpts
     */
    public function testRichExcerpt(string $fixtureDir) {
        list($body, $expected) = $this->getFixture($this->getFixtureDir() . "/rich/excerpt/" . $fixtureDir);
        $actual = \Gdn_Format::excerpt($body, RichFormat::FORMAT_KEY);
        $this->assertEquals(
            $expected,
            $actual,
            "Expected excerpt outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * Provide data for testing the excerpt method with the rich format.
     *
     * @return array
     */
    public function provideRichExcerpts(): array {
        return $this->createFixtureDataProvider("/formats/rich/excerpt");
    }

    /**
     * Test results of Gdn_Format::plainText with rich-formatted content.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     * @dataProvider provideRichPlainText
     */
    public function testRichPlainText(string $fixtureDir) {
        list($body, $expected) = $this->getFixture($this->getFixtureDir() . "/rich/plain-text/" . $fixtureDir);
        $actual = \Gdn_Format::plainText($body, RichFormat::FORMAT_KEY);
        $this->assertEquals(
            $expected,
            $actual,
            "Expected excerpt outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * Provide data for testing the plainText method.
     *
     * @return array
     */
    public function provideRichPlainText(): array {
        return $this->createFixtureDataProvider("/formats/rich/plain-text");
    }

    /**
     * Test using a rich-format array of operations with the quoteEmbed method.
     */
    public function testRichQuoteEmbedAsArray() {
        $richEmbed = [
            ["insert" => "Hello world."]
        ];

        $this->assertEquals(
            "<p>Hello world.</p>",
            \Gdn_Format::quoteEmbed($richEmbed, RichFormat::FORMAT_KEY)
        );
    }

    /**
     * Test the wysiwyg HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideWysiwyg
     */
    public function testWysiwygToHtml(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture($this->getFixtureDir() . '/wysiwyg/html/' . $fixtureDir);
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

    private function getFixtureDir() {
        return self::$fixtureRoot . '/formats';
    }
}
