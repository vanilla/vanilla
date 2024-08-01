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
abstract class AbstractFormatTestCase extends MinimalContainerTestCase
{
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
    public function htmlProvider(): array
    {
        return $this->makeDataProvider("getHtml", "HTML");
    }

    private function formatKey(): string
    {
        return $this->prepareFormatter()::FORMAT_KEY;
    }

    /**
     * Get a format service interface.
     *
     * @return FormatService
     */
    private function formatService(): FormatService
    {
        $preparedFormat = $this->prepareFormatter();
        $service = self::container()->get(FormatService::class);
        $service->registerFormat($this->formatKey(), $preparedFormat);
        return $service;
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
    public function testRenderHtml(string $input, string $expectedOutput, string $errorMessage)
    {
        $format = $this->formatService();
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput,
            $format->renderHTML($input, $this->formatKey()),
            $errorMessage
        );
        // Now with a parse first
        $parsed = $format->parse($input, $this->formatKey());
        $this->assertHtmlStringEqualsHtmlString($expectedOutput, $format->renderHTML($parsed), $errorMessage);
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function excerptProvider(): array
    {
        return $this->makeDataProvider("getExcerpt", "Excerpt");
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
    public function testRenderExcerpt(string $input, string $expectedOutput, string $errorMessage)
    {
        $format = $this->formatService();
        $this->assertEquals(trim($expectedOutput), $format->renderExcerpt($input, $this->formatKey()), $errorMessage);
        $parsed = $format->parse($input, $this->formatKey());
        $this->assertEquals(trim($expectedOutput), $format->renderExcerpt($parsed, $this->formatKey()), $errorMessage);
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function plainTextProvider(): array
    {
        return $this->makeDataProvider("getText", "Plain Text");
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
    public function testRenderPlainText(string $input, string $expectedOutput, string $errorMessage)
    {
        $format = $this->formatService();
        $this->assertEquals(
            trim($expectedOutput), // We can have extra trailing whitespace from our IDE.
            $format->renderPlainText($input, $this->formatKey()),
            $errorMessage
        );

        $parsed = $format->parse($input, $this->formatKey());
        $this->assertEquals(
            trim($expectedOutput), // We can have extra trailing whitespace from our IDE.
            $format->renderPlainText($parsed, $this->formatKey()),
            $errorMessage . " (pre-parsed)"
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function quoteProvider(): array
    {
        return $this->makeDataProvider("getQuote", "Quote");
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
    public function testRenderQuote(string $input, string $expectedOutput, string $errorMessage)
    {
        $format = $this->formatService();
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput,
            $format->renderQuote($input, $this->formatKey()),
            $errorMessage
        );
        $parsed = $format->parse($input, $this->formatKey());
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput,
            $format->renderQuote($parsed, $this->formatKey()),
            $errorMessage
        );
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function headingsProvider(): array
    {
        return $this->makeDataProvider("getHeadings", "Headings");
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider headingsProvider
     */
    public function testParseHeadings(string $input, array $expectedOutput)
    {
        $format = $this->formatService();
        $headings = $format->parseHeadings($input, $this->formatKey());
        $this->assertEquals(json_encode($expectedOutput, JSON_PRETTY_PRINT), json_encode($headings, JSON_PRETTY_PRINT));
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function imageProvider(): array
    {
        return $this->makeDataProvider("getImages", "Images");
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider imageProvider
     */
    public function testParseImages(string $input, array $expectedOutput)
    {
        $format = $this->formatService();
        $images = $format->parseImages($input, $this->formatKey());
        $this->assertEquals($expectedOutput, $images);
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function imageUrlProvider(): array
    {
        return $this->makeDataProvider("getImageUrls", "Images");
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider imageUrlProvider
     */
    public function testParseImageUrls(string $input, array $expectedOutput)
    {
        $format = $this->formatService();
        $images = $format->parseImageUrls($input, $this->formatKey());
        $this->assertEquals(json_encode($expectedOutput, JSON_PRETTY_PRINT), json_encode($images, JSON_PRETTY_PRINT));
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function attachmentProvider(): array
    {
        return $this->makeDataProvider("getAttachments", "attachments");
    }

    /**
     * Test heading parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider attachmentProvider
     */
    public function testParseAttachments(string $input, array $expectedOutput)
    {
        $format = $this->formatService();
        $headings = $format->parseAttachments($input, $this->formatKey());
        $this->assertEquals(json_encode($expectedOutput, JSON_PRETTY_PRINT), json_encode($headings, JSON_PRETTY_PRINT));
    }

    /**
     * PHPUnit data provider.
     *
     * @return array
     */
    public function mentionsProvider(): array
    {
        return $this->makeDataProvider("getMentions", "Mentions");
    }

    /**
     * Test mention parsing of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider mentionsProvider
     */
    public function testParseMentions(string $input, array $expectedOutput)
    {
        $format = $this->formatService();
        $this->assertSame($expectedOutput, $format->parseMentions($input, $this->formatKey()));
    }

    /**
     * Data provider for testParseAllMentions.
     *
     * @return array
     */
    public function allMentionsProvider(): array
    {
        return $this->makeDataProvider("getAllMentions", "allMentions");
    }

    /**
     * Test the parseAllMentions method of the format against fixtures.
     *
     * @param string $input
     * @param array $expectedOutput
     *
     * @dataProvider allMentionsProvider
     */
    public function testParseAllMentions(string $input, array $expectedOutput)
    {
        $format = $this->formatService();
        $this->assertSame($expectedOutput, $format->parseAllMentions($input, $this->formatKey()));
    }

    /**
     * Test parseImageUrls when the format is null.
     */
    public function testParseImageUrlsNullFormat()
    {
        $formatService = $this->formatService();
        $result = $formatService->parseImageUrls("test content", null);
        $this->assertEquals([], $result, "NotFoundFormat::parseImageUrl returns an empty array");
    }

    /**
     * Test parseImageUrls excludes emojis.
     */
    public function testParseImageUrlsExcludeEmojis()
    {
        $formatService = $this->formatService();
        $content =
            '<img class="emoji" src="http://dev.vanilla.localhost/resources/emoji/smile.png" title=":)" alt=":)" height="20">';
        $result = $formatService->parseImageUrls($content, $this->formatKey());
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
    private function makeDataProvider(string $methodToCall, string $renderType): array
    {
        $paramSets = [];
        $fixtures = $this->prepareFixtures();
        foreach ($fixtures as $fixture) {
            $expected = $fixture->{$methodToCall}();
            if ($expected !== null) {
                $paramSets[$fixture->getName()] = [
                    $fixture->getInput(),
                    $expected,
                    "Failed asserting expected fixture output for $renderType fixture '{$fixture->getName()}'",
                ];
            }
        }

        if (count($paramSets) === 0) {
            $this->markTestSkipped("Could not find a $renderType fixture.");
        }
        return $paramSets;
    }
}
