<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\BBCodeFormat;
use Vanilla\Formatting\Formats\DisplayFormat;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Formatting\Formats\WysiwygFormat;
use VanillaTests\SiteTestCase;

/**
 * Tests for the UserMention removal.
 */
class UserMentionsRemovalTest extends SiteTestCase
{
    use AssertsFixtureRenderingTrait;

    const USERNAME_NO_SPACE = "UserToAnonymize";
    const USERNAME_WITH_SPACE = "User To Anonymize";
    const USERNAME_ANONYMIZE = "[Deleted User]";

    const PROFILE_URL_NO_SPACE = "/profile/UserToAnonymize";
    const PROFILE_URL_WITH_SPACE = "/profile/User%20To%20Anonymize";
    const PROFILE_URL_ANONYMIZE = "/profile/%5BDeleted%20User%5D";

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        // Make sure the quote embed is registered.
        /** @var EmbedService $embedService */
        $embedService = \Gdn::getContainer()->get(EmbedService::class);
        $embedService->registerEmbed(QuoteEmbed::class, QuoteEmbed::TYPE);
    }

    /**
     * Test the anonymization of Markdown posts.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideMarkdownQuote
     * @dataProvider provideNonRichAtMention
     * @dataProvider provideUserNameUrl
     */
    public function testMarkdownAnonymization(
        string $body,
        string $expected,
        string $username = self::USERNAME_NO_SPACE
    ) {
        $formatter = self::container()->get(MarkdownFormat::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    /**
     * Provide Markdown quotes body/expected result in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function provideMarkdownQuote(): array
    {
        $r = [
            "validQuoteNoSpace" => [
                '> @UserToAnonymize said:
                 > UserToAnonymize is an amazing human slash genius.

                 Markdown quote',
                '> @"[Deleted User]" said:
                 > UserToAnonymize is an amazing human slash genius.

                 Markdown quote',
            ],
            "validQuoteWithSpace" => [
                '> @"User To Anonymize" said:
                 > User To Anonymize is an amazing human slash genius.
                 Markdown quote',
                '> @"[Deleted User]" said:
                 > User To Anonymize is an amazing human slash genius.
                 Markdown quote',
                self::USERNAME_WITH_SPACE,
            ],
            "invalidQuoteBefore" => [
                '> @1UserToAnonymize said:
                 > UserToAnonymize is an amazing human slash genius.

                 Markdown quote',
                '> @1UserToAnonymize said:
                 > UserToAnonymize is an amazing human slash genius.

                 Markdown quote',
            ],
            "invalidQuoteAfter" => [
                '> @UserToAnonymize1 said:
                 > UserToAnonymize is an amazing human slash genius.

                 Markdown quote',
                '> @UserToAnonymize1 said:
                 > UserToAnonymize is an amazing human slash genius.

                 Markdown quote',
            ],
        ];
        return $r;
    }

    /**
     * Test the anonymization of text posts.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideMarkdownQuote
     * @dataProvider provideNonRichAtMention
     * @dataProvider provideUserNameUrl
     */
    public function testTextAnonymization(string $body, string $expected, string $username = self::USERNAME_NO_SPACE)
    {
        $formatter = self::container()->get(TextFormat::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the anonymization of BBCode posts.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideBBCodeQuote
     * @dataProvider provideNonRichAtMention
     * @dataProvider provideUserNameUrl
     */
    public function testBBCodeAnonymization(string $body, string $expected, string $username = self::USERNAME_NO_SPACE)
    {
        $formatter = self::container()->get(BBCodeFormat::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    /**
     * Provide BBCode post body/expected result in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function provideBBCodeQuote(): array
    {
        $r = [
            "validQuote" => [
                '[quote="UserToAnonymize;d-1"]UserToAnonymize is an amazing human slash genius.[/quote]',
                '[quote="[Deleted User];d-1"]UserToAnonymize is an amazing human slash genius.[/quote]',
            ],
            "invalidQuoteBefore" => [
                '[quote="0UserToAnonymize;d-1"]UserToAnonymize is an amazing human slash genius.[/quote]',
                '[quote="0UserToAnonymize;d-1"]UserToAnonymize is an amazing human slash genius.[/quote]',
            ],
            "invalidQuoteAfter" => [
                '[quote="UserToAnonymize1;d-1"]UserToAnonymize is an amazing human slash genius.[/quote]',
                '[quote="UserToAnonymize1;d-1"]UserToAnonymize is an amazing human slash genius.[/quote]',
            ],
        ];
        return $r;
    }

    /**
     * Test the anonymization of Html posts.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideHtmlQuote
     * @dataProvider provideNonRichAtMention
     * @dataProvider provideUserNameUrl
     */
    public function testHtmlAnonymization(string $body, string $expected, string $username = self::USERNAME_NO_SPACE)
    {
        $formatter = self::container()->get(HtmlFormat::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    /**
     * Provide Html post body/expected result in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function provideHtmlQuote(): array
    {
        $r = [
            "validQuote" => [
                '<blockquote class="Quote" rel="UserToAnonymize">UserToAnonymize is an amazing human slash genius.</blockquote>',
                '<blockquote class="Quote" rel="[Deleted User]">UserToAnonymize is an amazing human slash genius.</blockquote>',
            ],
            "invalidQuoteBefore" => [
                '<blockquote class="Quote" rel="1UserToAnonymize">UserToAnonymize is an amazing human slash genius.</blockquote>',
                '<blockquote class="Quote" rel="1UserToAnonymize">UserToAnonymize is an amazing human slash genius.</blockquote>',
            ],
            "invalidQuoteAfter" => [
                '<blockquote class="Quote" rel="UserToAnonymize1">UserToAnonymize is an amazing human slash genius.</blockquote>',
                '<blockquote class="Quote" rel="UserToAnonymize1">UserToAnonymize is an amazing human slash genius.</blockquote>',
            ],
        ];
        return $r;
    }

    /**
     * Test the anonymization of Wysiwyg posts.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideWysiwygPost
     * @dataProvider provideNonRichAtMention
     * @dataProvider provideUserNameUrl
     */
    public function testWysiwygAnonymization(string $body, string $expected, string $username = self::USERNAME_NO_SPACE)
    {
        $formatter = self::container()->get(WysiwygFormat::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    /**
     * Provide Wysiwyg post body/expected result in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function provideWysiwygPost(): array
    {
        $baseUrl = $this->getBaseUrl();
        return [
            "validQuoteNoSpace" => [
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="/profile/UserToAnonymize" class="js-userCard" data-userid="1">UserToAnonymize</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="' .
                $baseUrl .
                self::PROFILE_URL_ANONYMIZE .
                '" class="js-userCard" data-userid="1">[Deleted User]</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
            ],
            "validQuoteWithSpace" => [
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="/profile/User%20To%20Anonymize" class="js-userCard" data-userid="1">User To Anonymize</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="' .
                $baseUrl .
                self::PROFILE_URL_ANONYMIZE .
                '" class="js-userCard" data-userid="1">[Deleted User]</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
                self::USERNAME_WITH_SPACE,
            ],
            "invalidQuoteBefore" => [
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="/profile/1UserToAnonymize" class="js-userCard" data-userid="1">1UserToAnonymize</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="/profile/1UserToAnonymize" class="js-userCard" data-userid="1">1UserToAnonymize</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
            ],
            "invalidQuoteAfter" => [
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="/profile/UserToAnonymize2" class="js-userCard" data-userid="1">UserToAnonymize2</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
                '<blockquote class="Quote"><div class="QuoteAuthor"><a href="/profile/UserToAnonymize2" class="js-userCard" data-userid="1">UserToAnonymize2</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
            ],
            "inlineTextMention" => [
                "</div>UserToAnonymize is an amazing human slash genius.</div>",
                "</div>UserToAnonymize is an amazing human slash genius.</div>",
            ],
        ];
    }

    /**
     * Test the anonymization of Rich quotes.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideRichPost
     */
    public function testRichAnonymization(string $body, string $expected, string $username = self::USERNAME_NO_SPACE)
    {
        $formatter = self::container()->get(RichFormat::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    /**
     * Provide Rich post body/expected result in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function provideRichPost(): array
    {
        $baseUrl = $this->getBaseUrl();
        $profileUrlNoSpace = $baseUrl . self::PROFILE_URL_NO_SPACE;
        $profileUrlAnonymize = $baseUrl . self::PROFILE_URL_ANONYMIZE;

        return [
            "remove at-mention" => [
                json_encode([
                    [
                        "insert" => [
                            "mention" => [
                                "name" => "UserToAnonymize",
                                "userID" => 1,
                            ],
                        ],
                    ],
                ]),
                json_encode([
                    [
                        "insert" => [
                            "mention" => [
                                "name" => "[Deleted User]",
                                "userID" => -1,
                            ],
                        ],
                    ],
                ]),
            ],
            "remove profile url" => [
                json_encode([
                    [
                        "attributes" => [
                            "link" => $profileUrlNoSpace,
                        ],
                        "insert" => "my profile link",
                    ],
                ]),
                json_encode([
                    [
                        "attributes" => [
                            "link" => $profileUrlAnonymize,
                        ],
                        "insert" => "[Deleted User]",
                    ],
                ]),
            ],
            "remove user info from quote" => [
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "body" => "<p>test</p>",
                                    "bodyRaw" => '[{"insert":"test\n"}]',
                                    "format" => "rich",
                                    "insertUser" => [
                                        "userID" => 2,
                                        "name" => "UserToAnonymize",
                                        "url" => $profileUrlNoSpace,
                                        "photoUrl" => "defaulticon.png",
                                        "dateLastActive" => "2022-06-02T16:20:42+00:00",
                                        "label" => "yay",
                                    ],
                                    "embedType" => "quote",
                                ],
                            ],
                        ],
                    ],
                ]),
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "body" => "<p>test</p>",
                                    "bodyRaw" => '[{"insert":"test\n"}]',
                                    "format" => "rich",
                                    "insertUser" => [
                                        "userID" => -1,
                                        "name" => "[Deleted User]",
                                        "url" => $profileUrlAnonymize,
                                        "photoUrl" => null,
                                        "dateLastActive" => "1970-01-01T00:00:00+00:00",
                                        "label" => "",
                                    ],
                                    "embedType" => "quote",
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            "remove user info from quote content of a different format" => [
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "body" => <<<EOT

<blockquote class="Quote blockquote">test</blockquote>
<a href="$profileUrlNoSpace" rel="nofollow">@UserToAnonymize</a>: test<br><a rel="nofollow" href="$profileUrlNoSpace">test</a><br><br>
EOT
                                    ,
                                    "bodyRaw" => <<<EOT

<blockquote class="Quote" rel="UserToAnonymize">test</blockquote>
@UserToAnonymize: test
<a href="$profileUrlNoSpace">test</a>


EOT
                                    ,
                                    "format" => "html",
                                    "insertUser" => [
                                        "userID" => 2,
                                        "name" => "UserToAnonymize",
                                        "url" => $profileUrlNoSpace,
                                        "photoUrl" => "defaulticon.png",
                                        "dateLastActive" => "2022-06-02T16:20:42+00:00",
                                        "label" => "yay",
                                    ],
                                    "embedType" => "quote",
                                ],
                            ],
                        ],
                    ],
                ]),
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "body" => <<<EOT

<blockquote class="Quote blockquote">test</blockquote>
<a href="$profileUrlAnonymize" rel="nofollow">@[Deleted User]</a>: test<br><a rel="nofollow" href="$profileUrlAnonymize">test</a><br><br>
EOT
                                    ,
                                    "bodyRaw" => <<<EOT

<blockquote class="Quote" rel="[Deleted User]">test</blockquote>
@"[Deleted User]": test
<a href="$profileUrlAnonymize">test</a>


EOT
                                    ,
                                    "format" => "html",
                                    "insertUser" => [
                                        "userID" => -1,
                                        "name" => "[Deleted User]",
                                        "url" => $profileUrlAnonymize,
                                        "photoUrl" => null,
                                        "dateLastActive" => "1970-01-01T00:00:00+00:00",
                                        "label" => "",
                                    ],
                                    "embedType" => "quote",
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
        ];
    }

    /**
     * Return mention patterns that are common for all non-rich formats.
     *
     * @return array
     */
    public function provideNonRichAtMention(): array
    {
        $r = [
            "validAtMentionEndWithWhiteSpace" => [
                "@UserToAnonymize Some fluff text to make sure inline UserToAnonymize is not removed.",
                '@"[Deleted User]" Some fluff text to make sure inline UserToAnonymize is not removed.',
            ],
            "validAtMentionEndWithDot" => [
                "@UserToAnonymize. Some fluff text to make sure inline UserToAnonymize is not removed.",
                '@"[Deleted User]". Some fluff text to make sure inline UserToAnonymize is not removed.',
            ],
            "validAtMentionEndWithComma" => [
                "@UserToAnonymize, Some fluff text to make sure inline UserToAnonymize is not removed.",
                '@"[Deleted User]", Some fluff text to make sure inline UserToAnonymize is not removed.',
            ],
            "validAtMentionEndWithSemiColon" => [
                "@UserToAnonymize; Some fluff text to make sure inline UserToAnonymize is not removed.",
                '@"[Deleted User]"; Some fluff text to make sure inline UserToAnonymize is not removed.',
            ],
            "validAtMentionEndWithInterrogationMark" => [
                "@UserToAnonymize? Some fluff text to make sure inline UserToAnonymize is not removed.",
                '@"[Deleted User]"? Some fluff text to make sure inline UserToAnonymize is not removed.',
            ],
            "validAtMentionEndWithExclamationMark" => [
                "@UserToAnonymize! Some fluff text to make sure inline UserToAnonymize is not removed.",
                '@"[Deleted User]"! Some fluff text to make sure inline UserToAnonymize is not removed.',
            ],
            "validAtMentionEndWithSingleQuotw" => [
                '@UserToAnonymize\' Some fluff text to make sure inline UserToAnonymize is not removed.',
                '@"[Deleted User]"\' Some fluff text to make sure inline UserToAnonymize is not removed.',
            ],
            "validAtMentionEOF" => ["@UserToAnonymize", '@"[Deleted User]"'],
            "validAtMentionSkipLine" => [
                '@UserToAnonymize
                ',
                '@"[Deleted User]"
                ',
            ],
            "validAtMentionColon" => ["@UserToAnonymize:", '@"[Deleted User]":'],
            "validAtMentionWithSpace" => [
                '@"User To Anonymize" Some fluff text to make sure inline User To Anonymize is not removed.',
                '@"[Deleted User]" Some fluff text to make sure inline User To Anonymize is not removed.',
                self::USERNAME_WITH_SPACE,
            ],
            "invalidAtMentionBefore" => [
                "@0UserToAnonymize Some fluff text to make sure inline UserToAnonymize is not removed.",
                "@0UserToAnonymize Some fluff text to make sure inline UserToAnonymize is not removed.",
            ],
            "invalidAtMentionAfter" => [
                "@UserToAnonymize0 Some fluff text to make sure inline UserToAnonymize is not removed.",
                "@UserToAnonymize0 Some fluff text to make sure inline UserToAnonymize is not removed.",
            ],
            "inlineTextMentionNoSpace" => [
                "UserToAnonymize should not be removed for unbounded mention.",
                "UserToAnonymize should not be removed for unbounded mention.",
            ],
            "inlineTextMentionWithSpace" => [
                "User To Anonymize should not be removed for unbounded mention.",
                "User To Anonymize should not be removed for unbounded mention.",
                self::USERNAME_WITH_SPACE,
            ],
        ];
        return $r;
    }

    /**
     * Return mention patterns that are common for all non-rich formats.
     *
     * @return array
     */
    public function provideUserNameUrl(): array
    {
        $baseUrl = $this->getBaseUrl();

        $r = [
            "validUrlNoSpace" => [$baseUrl . self::PROFILE_URL_NO_SPACE, $baseUrl . self::PROFILE_URL_ANONYMIZE],
            "validUrlWithSpace" => [
                $baseUrl . self::PROFILE_URL_WITH_SPACE,
                $baseUrl . self::PROFILE_URL_ANONYMIZE,
                self::USERNAME_WITH_SPACE,
            ],
            "invalidUrlBefore" => [$baseUrl . "/profile/1UserToAnonymize", $baseUrl . "/profile/1UserToAnonymize"],
            "invalidUrlAfter" => [$baseUrl . "/profile/UserToAnonymize1", $baseUrl . "/profile/UserToAnonymize1"],
            "invalidUrlOtherCommunity" => [
                "https://dev.vanilla.com/profile/UserToAnonymize",
                "https://dev.vanilla.com/profile/UserToAnonymize",
            ],
        ];
        return $r;
    }

    /**
     * Test the anonymization of Display posts.
     *
     * This is a legacy format that do not contain atMentions or quotes.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideUserNameUrl
     */
    public function testDisplayAnonymization(string $body, string $expected, string $username = self::USERNAME_NO_SPACE)
    {
        $formatter = self::container()->get(DisplayFormat::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    private function getBaseUrl()
    {
        return preg_replace("/:[0-9]+/", "", getenv("TEST_BASEURL")) . "/usermentionsremovaltest";
    }
}
