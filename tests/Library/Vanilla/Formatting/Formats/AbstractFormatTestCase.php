<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\Formatting\FormatFixture;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;

/**
 * Base test case for a format. Provides test cases for all core methods on the interface.
 */
abstract class AbstractFormatTestCase extends ContainerTestCase {

    use AssertsFixtureRenderingTrait;

    /**
     * @return FormatInterface
     */
    abstract protected function prepareFormatter(): FormatInterface;

    /**
     * Get all of the fixtures to run the tests with.
     * @return FormatFixture[]
     */
    abstract protected function prepareFixtures(): array;

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function htmlProvider(): array {
        return $this->makeDataProvider('getHtml', 'HTML');
    }

    /**
     * Test the HTML rendering of the format against fixtures.
     *
     * @param string $input
     * @param string $expectedOutput
     * @param string $errorMessage
     *
     * @dataProvider htmlProvider
     */
    public function testRenderHtml(string $input, string $expectedOutput, string $errorMessage) {
        $format = $this->prepareFormatter();
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput,
            $format->renderHTML($input),
            $errorMessage
        );
    }


    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function excerptProvider(): array {
        return $this->makeDataProvider('getExcerpt', 'Excerpt');
    }

    /**
     * Test the excerpt rendering of the format against fixtures.
     *
     * @param string $input
     * @param string $expectedOutput
     * @param string $errorMessage
     *
     * @dataProvider excerptProvider
     */
    public function testRenderExcerpt(string $input, string $expectedOutput, string $errorMessage) {
        $format = $this->prepareFormatter();
        $this->assertEquals(
            $expectedOutput,
            $format->renderExcerpt($input),
            $errorMessage
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function plainTextProvider(): array {
        return $this->makeDataProvider('getText', 'Plain Text');
    }

    /**
     * Test plainText rendering of the format against fixtures.
     *
     * @param string $input
     * @param string $expectedOutput
     * @param string $errorMessage
     *
     * @dataProvider plainTextProvider
     */
    public function testRenderPlainText(string $input, string $expectedOutput, string $errorMessage) {
        $format = $this->prepareFormatter();
        $this->assertEquals(
            trim($expectedOutput), // We can have extra trailing whitespace from our IDE.
            $format->renderPlainText($input),
            $errorMessage
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function quoteProvider(): array {
        return $this->makeDataProvider('getQuote', 'Quote');
    }

    /**
     * Test quote rendering of the format against fixtures.
     *
     * @param string $input
     * @param string $expectedOutput
     * @param string $errorMessage
     *
     * @dataProvider quoteProvider
     */
    public function testRenderQuote(string $input, string $expectedOutput, string $errorMessage) {
        $format = $this->prepareFormatter();
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput,
            $format->renderQuote($input),
            $errorMessage
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function headingsProvider(): array {
        return $this->makeDataProvider('getHeadings', 'Headings');
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider headingsProvider
     */
    public function testParseHeadings(string $input, array $expectedOutput) {
        $format = $this->prepareFormatter();
        $this->assertSame(
            $expectedOutput,
            $format->parseHeadings($input)
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function mentionsProvider(): array {
        return $this->makeDataProvider('getMentions', 'Mentions');
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider mentionsProvider
     */
    public function testParseMentions(string $input, array $expectedOutput) {
        $format = $this->prepareFormatter();
        $this->assertSame(
            $expectedOutput,
            $format->renderExcerpt($input)
        );
    }

    /**
     * Generate a PHPUnit data provider.
     *
     * @param string $methodToCall The name of the method to call on the fixture for getting the expected output.
     * @param string $renderType The name to print if the test is skipped.
     *
     * @return array
     */
    private function makeDataProvider(string $methodToCall, string $renderType): array {
        $paramSets = [];
        $fixtures = $this->prepareFixtures();
        foreach ($fixtures as $fixture) {
            $expected = $fixture->{$methodToCall}();
            if ($expected !== null) {
                $paramSets[$fixture->getName()] = [
                    $fixture->getInput(),
                    $expected,
                    "Failed asserting expected fixture output for $renderType fixture '{$fixture->getName()}'"
                ];
            }
        }

        if (count($paramSets) === 0) {
            $this->markTestSkipped("Could not find a $renderType fixture.");
        }
        return $paramSets;
    }
}
