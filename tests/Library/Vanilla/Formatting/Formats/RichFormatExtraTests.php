<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats\RichFormatParsed;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;
use VanillaTests\SiteTestCase;

class RichFormatExtraTests extends SiteTestCase
{
    use UserMentionTestTraits;

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        self::container()
            ->rule(EmbedService::class)
            ->addCall("registerEmbed", [ImageEmbed::class, ImageEmbed::TYPE]);
        return self::container()->get(RichFormat::class);
    }
    /**
     * Test parseImageUrls excludes emojis.
     */
    public function testParseImageUrlsExcludeEmojis()
    {
        $formatService = $this->prepareFormatter();
        $content = '[{"insert":{"emoji":{"emojiChar":"😀"}}},{"insert":"\n"}]';
        $result = $formatService->parseImageUrls($content);
        $this->assertEquals([], $result);
    }

    /**
     * @param string $body
     * @param array $expected
     * @dataProvider provideAllRichMentions
     */
    public function testAllUserMentionParsing(string $body, array $expected = ["UserNoSpace"])
    {
        $result = $this->prepareFormatter()->parseAllMentions($body);
        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function provideAllRichMentions(): array
    {
        $baseUrl = $this->getBaseUrl();
        return [
            "valid at mention" => [
                json_encode([
                    [
                        "insert" => [
                            "mention" => [
                                "name" => $this->USERNAME_NO_SPACE,
                                "userID" => 1,
                            ],
                        ],
                    ],
                ]),
            ],
            "valid quote" => [
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "bodyRaw" => json_encode([["insert" => "test"]]),
                                    "format" => "rich",
                                    "insertUser" => [
                                        "name" => $this->USERNAME_NO_SPACE,
                                    ],
                                    "embedType" => "quote",
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            "valid quote in a different format with multiple mentions" => [
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "bodyRaw" =>
                                        '<blockquote class="Quote" rel="User2">test</blockquote>@User3: test<a href="' .
                                        $baseUrl .
                                        '/profile/User4">test</a>',
                                    "format" => "html",
                                    "insertUser" => [
                                        "name" => $this->USERNAME_NO_SPACE,
                                    ],
                                    "embedType" => "quote",
                                ],
                            ],
                        ],
                    ],
                ]),
                [$this->USERNAME_NO_SPACE, "User2", "User3", "User4"],
            ],
            "valid url" => [
                json_encode([
                    [
                        "attributes" => [
                            "link" => $baseUrl . $this->PROFILE_URL_NO_SPACE,
                        ],
                        "insert" => "my profile $baseUrl/profile/UserUrlInText",
                    ],
                ]),
                [$this->USERNAME_NO_SPACE, "UserUrlInText"],
            ],
            "nested-rich-quote" => [
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "bodyRaw" => [
                                        [
                                            "insert" => [
                                                "mention" => [
                                                    "name" => "nested mention",
                                                    "userID" => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                    "format" => "rich",
                                    "insertUser" => [
                                        "name" => $this->USERNAME_NO_SPACE,
                                    ],
                                    "embedType" => "quote",
                                ],
                            ],
                        ],
                    ],
                ]),
                ["nested mention", $this->USERNAME_NO_SPACE],
            ],
        ];
    }

    /**
     * Test parse method with good input
     * @return void
     */
    public function testParseWithGoodInput()
    {
        $input = '[{"insert":"good input"}]';
        $expected = $input;

        /** @var RichFormatParsed $output */
        $output = $this->prepareFormatter()->parse($input);
        $this->assertInstanceOf(RichFormatParsed::class, $output);
        $this->assertInstanceOf(FormatParsedInterface::class, $output);
        $this->assertSame($expected, $output->getRawContent());
        $this->assertSame($expected, $output->getBlotGroups()->stringify()->text);
    }

    /**
     * Test parse method with bad input
     * @return void
     */
    public function testParseWithBadInput()
    {
        $input = "invalid input";
        $expected = '[{"insert":"' . RichFormat::RENDER_ERROR_MESSAGE . '"}]';

        /** @var RichFormatParsed $output */
        $output = $this->prepareFormatter()->parse($input);
        $this->assertInstanceOf(RichFormatParsed::class, $output);
        $this->assertInstanceOf(FormatParsedInterface::class, $output);
        $this->assertSame($expected, $output->getRawContent());
        $this->assertSame($expected, $output->getBlotGroups()->stringify()->text);
    }
}
