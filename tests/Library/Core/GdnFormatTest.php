<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Vanilla\Formatting\Formats\RichFormat;
use VanillaTests\SharedBootstrapTestCase;
use Gdn_Format;

/**
 * Tests for Gdn_Format.
 */
class GdnFormatTest extends SharedBootstrapTestCase {

    /**
     * Provide data for testing the excerpt method.
     *
     * @return array
     */
    public function provideForExcerpt(): array {
        // @codingStandardsIgnoreStart
        return [
            [
                RichFormat::FORMAT_KEY,
                file_get_contents(realpath(__DIR__ . "/../../fixtures/rich-posts/excerpt-test.json")),
                "https://vanillaforums.example/discussion/comment/1#Comment_1 This is a spoiler. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute…"
            ],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Provide data for testing the plainText method.
     *
     * @return array
     */
    public function provideForPlainText(): array {
        return [
            ["Markdown", "**Hello** world.", "Hello world."],
            [RichFormat::FORMAT_KEY, '[{"insert":"Hello world.\n"}]', "Hello world.\n"],
        ];
    }

    /**
     * Provide data for testing the quoteEmbed method.
     *
     * @return array
     */
    public function provideForQuoteEmbed(): array {
        return [
            [
                RichFormat::FORMAT_KEY,
                [
                    [
                        "insert" => "Hello world."
                    ]
                ],
                "<p>Hello world.</p>"
            ],
        ];
    }

    /**
     * Test results of Gdn_Format::excerpt.
     *
     * @param string $format Body format type (e.g. Markdown, Rich).
     * @param string $body Body contents.
     * @param string $expected Expected result of invoking excerpt.
     * @dataProvider provideForExcerpt
     */
    public function testExcerpt(string $format, string $body, string $expected) {
        $actual = Gdn_Format::excerpt($body, $format);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test results of Gdn_Format::plainText.
     *
     * @param string $format Body format type (e.g. Markdown, Rich).
     * @param string $body Body contents.
     * @param string $expected Expected result of invoking plainText.
     * @dataProvider provideForPlainText
     */
    public function testPlainText(string $format, string $body, string $expected) {
        $actual = Gdn_Format::plainText($body, $format);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test results of Gdn_Format::quoteEmbed.
     *
     * @param string $format Body format type (e.g. Markdown, Rich).
     * @param array|string $body Body contents. Most formats will be passed as strings. Rich-formatted posts will be passed as arrays.
     * @param string $expected Expected result of invoking quoteEmbed.
     * @dataProvider provideForQuoteEmbed
     */
    public function testQuoteEmbed(string $format, $body, string $expected) {
        $actual = Gdn_Format::quoteEmbed($body, $format);
        $this->assertEquals($expected, $actual);
    }
}
