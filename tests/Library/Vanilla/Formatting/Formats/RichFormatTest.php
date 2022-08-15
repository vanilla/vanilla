<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use PHPUnit\Framework\TestCase;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\RichFormat;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the RichFormat.
 */
class RichFormatTest extends TestCase
{
    use SiteTestTrait;
    use EventSpyTestTrait;
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
     * @inheritDoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("rich"))->getAllFixtures();
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
        $baseUrl = $this->getBaseUrl("richformattest");
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
                        "insert" => "my profile link",
                    ],
                ]),
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
}
