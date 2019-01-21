<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Formats\RichFormat;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Unit tests for the Gdn_Format class.
 */
class GdnFormatTest extends SharedBootstrapTestCase {

    use AssertsFixtureRenderingTrait;

    /**
     * Test the BBCode HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideBBCode
     */
    public function testBBCode(string $fixtureDir) {
        $this->assertFixturePassesForFormat($fixtureDir, 'bbcode');
    }

    /**
     * Test the markdown HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideMarkdown
     */
    public function testMarkdown(string $fixtureDir) {
        $this->assertFixturePassesForFormat($fixtureDir, 'markdown');
    }

    /**
     * Test the text HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideText
     */
    public function testText(string $fixtureDir) {
        $this->assertFixturePassesForFormat($fixtureDir, 'text');
    }

    /**
     * Test the textEx HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideTextEx
     */
    public function testTextEx(string $fixtureDir) {
        $this->assertFixturePassesForFormat($fixtureDir, 'textex');
    }

    /**
     * Test the HTML HTML rendering.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     *
     * @dataProvider provideHtml
     */
    public function testHtml(string $fixtureDir) {
        $this->assertFixturePassesForFormat($fixtureDir, 'html');
    }


    /**
     * Test results of Gdn_Format::excerpt with rich-formatted content.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     * @dataProvider provideRichExcerpts
     */
    public function testRichExcerpt(string $fixtureDir) {
        list($body, $unused, $expected) = $this->getFixture($fixtureDir);
        $actual = \Gdn_Format::excerpt($body, RichFormat::FORMAT_KEY);
        $this->assertEquals(
            $expected,
            $actual,
            "Expected excerpt outputs for fixture $fixtureDir did not match."
        );
    }


    /**
     * Test results of Gdn_Format::plainText with rich-formatted content.
     *
     * @param string $fixtureDir The directory of the fixture to use for the testCase.
     * @dataProvider provideRichPlainText
     */
    public function testRichPlainText(string $fixtureDir) {
        list($body, $unused, $expected) = $this->getFixture($fixtureDir);
        $actual = \Gdn_Format::plainText($body, RichFormat::FORMAT_KEY);
        $this->assertEquals(
            $expected,
            $actual,
            "Expected excerpt outputs for fixture $fixtureDir did not match."
        );
    }


    /**
     * Test using a rich-format array of operations with the quoteEmbed method.
     */
    public function testRichQuoteEmbedAsArray() {
        $richEmbed = [
            ["insert" => "Hello world."],
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
    public function testWysiwyg(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture($fixtureDir);
        $output = \Gdn_Format::wysiwyg($input);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    /**
     * Test the format with an array of all fixture inputs.
     *
     * @param array $fixtureDirs
     * @param callable $formatMethod
     *
     * @dataProvider groupInputModesProvider
     */
    public function testGroupInputModes(array $fixtureDirs, callable $formatMethod) {
        $this->assertAllFixturesAsArray($fixtureDirs, $formatMethod);
        $this->assertAllFixturesAsObject($fixtureDirs, $formatMethod);
    }

    /**
     * @return array
     */
    public function groupInputModesProvider(): array {
        return [
            [$this->provideHtml(), [\Gdn_Format::class, 'html']],
            [$this->provideWysiwyg(), [\Gdn_Format::class, 'wysiwyg']],
            [$this->provideText(), [\Gdn_Format::class, 'text']],
            [$this->provideMarkdown(), [\Gdn_Format::class, 'markdown']],
            [$this->provideBBCode(), [\Gdn_Format::class, 'bbcode']],
            [$this->provideTextEx(), [\Gdn_Format::class, 'textex']],
        ];
    }

    /**
     * @return array
     */
    public function provideHtml(): array {
        return $this->createFixtureDataProvider('/formats/html/html');
    }

    /**
     * @return array
     */
    public function provideBBCode(): array {
        return $this->createFixtureDataProvider('/formats/bbcode/html');
    }

    /**
     * @return array
     */
    public function provideMarkdown(): array {
        return $this->createFixtureDataProvider('/formats/markdown/html');
    }

    /**
     * @return array
     */
    public function provideText(): array {
        return $this->createFixtureDataProvider('/formats/text/html');
    }

    /**
     * @return array
     */
    public function provideTextEx(): array {
        return $this->createFixtureDataProvider('/formats/textex/html');
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
     * Provide data for testing the plainText method.
     *
     * @return array
     */
    public function provideRichPlainText(): array {
        return $this->createFixtureDataProvider("/formats/rich/plain-text");
    }

    /**
     * @return array
     */
    public function provideWysiwyg(): array {
        return $this->createFixtureDataProvider('/formats/wysiwyg/html');
    }

    /**
     * Assert HTML and text formatting for a particular format using some fixture.
     *
     * @param string $fixtureDir
     * @param string $format
     */
    public function assertFixturePassesForFormat(string $fixtureDir, string $format) {
        list($input, $expectedHtml, $expectedText) = $this->getFixture($fixtureDir);
        $outputHtml = \Gdn_Format::to($input, $format);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedHtml, // Needed so code blocks are equivalently decoded
            $outputHtml, // Gdn_Format does htmlspecialchars
            "Expected $format -> html conversion for fixture $fixtureDir to match."
        );

        $outputText = \Gdn_Format::plainText($input, $format);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedText,
            $outputText,
            "Expected $format -> text conversion for fixture $fixtureDir to match."
        );
    }

    /**
     * Assert that the given callable implements Gdn_Format::to's array input/output method.
     *
     * @param array $fixtureDirs
     * @param callable $formatMethod
     */
    private function assertAllFixturesAsArray(array $fixtureDirs, callable $formatMethod) {
        $allInputs = [];
        $expectedOutputs = [];

        foreach ($fixtureDirs as $fixtureDir) {
            list($input, $expectedOutput) = $this->getFixture($fixtureDir[0]);
            $allInputs[] = $input;
            $expectedOutputs[] = $expectedOutput;
        }

        $allOutputs = $formatMethod($allInputs);

        foreach ($expectedOutputs as $i => $expectedOutput) {
            $this->assertHtmlStringEqualsHtmlString($expectedOutput, $allOutputs[$i]);
        }
    }

    /**
     * Assert that the given callable implements Gdn_Format::to's array input/output method.
     *
     * @param array $fixtureDirs
     * @param callable $formatMethod
     */
    private function assertAllFixturesAsObject(array $fixtureDirs, callable $formatMethod) {
        $allInputs = new \stdClass();
        $expectedOutputs = [];

        foreach ($fixtureDirs as $i => $fixtureDir) {
            list($input, $expectedOutput) = $this->getFixture($fixtureDir[0]);
            $allInputs->$i = $input;
            $expectedOutputs[] = $expectedOutput;
        }

        $allOutputs = $formatMethod($allInputs);

        foreach ($expectedOutputs as $i => $expectedOutput) {
            $this->assertHtmlStringEqualsHtmlString($expectedOutput, $allOutputs->$i);
        }
    }
}
