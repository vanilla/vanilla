<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\FormatService;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\Formatting\FormatFixture;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;

/**
 * Base test case for a format. Provides test cases for all core methods on the interface.
 */
abstract class AbstractFormatTestCase extends MinimalContainerTestCase {

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
            trim($expectedOutput),
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
        $headings = $format->parseHeadings($input);
        $this->assertEquals(
            json_encode($expectedOutput, JSON_PRETTY_PRINT),
            json_encode($headings, JSON_PRETTY_PRINT)
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function imageProvider(): array {
        return $this->makeDataProvider('getImages', 'Images');
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider imageProvider
     */
    public function testParseImages(string $input, array $expectedOutput) {
        $format = $this->prepareFormatter();
        $images = $format->parseImages($input);
        $this->assertEquals($expectedOutput, $images);
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function imageUrlProvider(): array {
        return $this->makeDataProvider('getImageUrls', 'Images');
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider imageUrlProvider
     */
    public function testParseImageUrls(string $input, array $expectedOutput) {
        $format = $this->prepareFormatter();
        $images = $format->parseImageUrls($input);
        $this->assertEquals(
            json_encode($expectedOutput, JSON_PRETTY_PRINT),
            json_encode($images, JSON_PRETTY_PRINT)
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function attachmentProvider(): array {
        return $this->makeDataProvider('getAttachments', 'attachments');
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider attachmentProvider
     */
    public function testParseAttachments(string $input, array $expectedOutput) {
        $format = $this->prepareFormatter();
        $headings = $format->parseAttachments($input);
        $this->assertEquals(
            json_encode($expectedOutput, JSON_PRETTY_PRINT),
            json_encode($headings, JSON_PRETTY_PRINT)
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
            $format->parseMentions($input)
        );
    }

    /**
     * Test parseImageUrls when the format is null.
     */
    public function testParseImageUrlsNullFormat() {
        $formatService =  self::container()->get(FormatService::class);
        $result = $formatService->parseImageUrls('test content', null);
        $this->assertEquals([], $result, 'NotFoundFormat::parseImageUrl returns an empty array');
    }

    /**
     * Test parseImageUrls excludes emojis.
     */
    public function testParseImageUrlsExcludeEmojis() {
        $formatService = $this->prepareFormatter();
        $content = '<img class="emoji" src="http://dev.vanilla.localhost/resources/emoji/smile.png" title=":)" alt=":)" height="20">';
        $result = $formatService->parseImageUrls($content);
        $this->assertEquals([], $result);
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
