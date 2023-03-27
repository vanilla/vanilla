<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use Vanilla\CurrentTimeStamp;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\BBCodeFormat;
use Vanilla\Formatting\Formats\DisplayFormat;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Formatting\Formats\Rich2Format;
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

    private int $mockedTimeStamp = 1675123472;

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
        $embedService->registerFilter($this->container()->get(QuoteEmbedFilter::class));
        CurrentTimeStamp::mockTime($this->mockedTimeStamp);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        CurrentTimeStamp::clearMockTime();
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
                '" class="js-userCard" data-userid="-1">[Deleted User]</a> said:</div>
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
                '" class="js-userCard" data-userid="-1">[Deleted User]</a> said:</div>
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
            "Alternate quote format" => [
                '<blockquote class="Quote">
                 <div><a rel="nofollow">UserToAnonymize</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
                '<blockquote class="Quote">
                 <div><a rel="nofollow">[Deleted User]</a> said:</div>
                 <div>UserToAnonymize is an amazing human slash genius.</div>
                 </blockquote>',
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
                        "insert" => "my profile $profileUrlNoSpace",
                    ],
                ]),
                json_encode([
                    [
                        "attributes" => [
                            "link" => $profileUrlAnonymize,
                        ],
                        "insert" => "my profile $profileUrlAnonymize",
                    ],
                ]),
            ],
            "remove user info from quote" => [
                json_encode([
                    [
                        "insert" => [
                            "embed-external" => [
                                "data" => [
                                    "recordID" => 1365,
                                    "recordType" => "comment",
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
                                    "dateInserted" => "2023-01-27T20:53:01+00:00",
                                    "insertUser" => [
                                        "userID" => 2,
                                        "name" => "UserToAnonymize",
                                        "url" => $profileUrlNoSpace,
                                        "photoUrl" => "defaulticon.png",
                                        "dateLastActive" => "2022-06-02T16:20:42+00:00",
                                        "label" => "yay",
                                    ],
                                    "url" => "https://dev.vanilla.localhost/discussion/comment/1365#Comment_1365",
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
                                    "recordID" => 1365,
                                    "recordType" => "comment",
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
                                    "dateInserted" => "2023-01-27T20:53:01+00:00",
                                    "insertUser" => [
                                        "userID" => 0,
                                        "name" => "unknown",
                                        "url" => $baseUrl . "/profile/",
                                        "photoUrl" =>
                                            $baseUrl . "/applications/dashboard/design/images/defaulticon.png",
                                        "dateLastActive" => date("c", $this->mockedTimeStamp),
                                        "banned" => 0,
                                        "punished" => 0,
                                        "private" => false,
                                    ],
                                    "displayOptions" => [
                                        "showUserLabel" => false,
                                        "showCompactUserInfo" => true,
                                        "showDiscussionLink" => false,
                                        "showPostLink" => false,
                                        "showCategoryLink" => false,
                                        "renderFullContent" => false,
                                        "expandByDefault" => false,
                                    ],
                                    "url" => "https://dev.vanilla.localhost/discussion/comment/1365#Comment_1365",
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
     * Test the anonymization of Rich quotes.
     *
     * @param string $body
     * @param string $expected
     * @param string $username
     * @dataProvider provideRich2Data
     */
    public function testRich2Anonymization(string $body, string $expected, string $username = self::USERNAME_NO_SPACE)
    {
        $formatter = self::container()->get(Rich2Format::class);
        $result = $formatter->removeUserPII($username, $body);
        $this->assertEquals($expected, $result);
    }

    /**
     * Provide Rich post body/expected result in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function provideRich2Data(): array
    {
        $baseUrl = $this->getBaseUrl();
        $profileUrlNoSpace = $baseUrl . self::PROFILE_URL_NO_SPACE;
        $profileUrlAnonymize = $baseUrl . self::PROFILE_URL_ANONYMIZE;
        $usernameNoSpace = self::USERNAME_NO_SPACE;
        $usernameAnonymize = self::USERNAME_ANONYMIZE;

        return [
            "remove at-mention" => [
                json_encode([
                    [
                        "type" => "p",
                        "children" => [
                            [
                                "type" => "@",
                                "children" => [
                                    0 => [
                                        "text" => "",
                                    ],
                                ],
                                "userID" => 7,
                                "name" => self::USERNAME_NO_SPACE,
                                "url" => $profileUrlNoSpace,
                                "photoUrl" =>
                                    "https://dev.vanilla.localhost/applications/dashboard/design/images/defaulticon.png",
                                "dateLastActive" => "2022-12-20T17:41:59+00:00",
                                "banned" => 0,
                                "private" => false,
                                "domID" => "mentionSuggestion7",
                                "value" => "",
                            ],
                        ],
                    ],
                ]),
                json_encode([
                    [
                        "type" => "p",
                        "children" => [
                            [
                                "type" => "@",
                                "userID" => -1,
                                "name" => self::USERNAME_ANONYMIZE,
                                "url" => $profileUrlAnonymize,
                                "photoUrl" =>
                                    "https://dev.vanilla.localhost/applications/dashboard/design/images/defaulticon.png",
                                "dateLastActive" => "2022-12-20T17:41:59+00:00",
                                "banned" => 0,
                                "private" => false,
                                "domID" => "mentionSuggestion7",
                                "value" => "",
                                "children" => [
                                    [
                                        "text" => "",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            "remove profile url" => [
                json_encode([
                    [
                        "type" => "p",
                        "children" => [
                            [
                                "type" => "a",
                                "url" => $profileUrlNoSpace,
                                "target" => "_self",
                                "children" => [
                                    [
                                        "text" => "my profile $profileUrlNoSpace",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                json_encode([
                    [
                        "type" => "p",
                        "children" => [
                            [
                                "type" => "a",
                                "url" => $profileUrlAnonymize,
                                "target" => "_self",
                                "children" => [
                                    [
                                        "text" => "my profile $profileUrlAnonymize",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            "remove mentions in quote blocks" => [
                json_encode([
                    [
                        "type" => "rich_embed_card",
                        "dataSourceType" => "url",
                        "url" => "https://dev.vanilla.localhost/discussion/1362/test",
                        "embedData" => [
                            "recordID" => 1362,
                            "recordType" => "discussion",
                            "body" => <<<EOD
<blockquote class="Quote blockquote">abc</blockquote>
<a href="$profileUrlNoSpace" rel="nofollow">@$usernameNoSpace</a> test<br><br><a rel="nofollow" href="$profileUrlNoSpace">$profileUrlNoSpace</a>
EOD
                            ,
                            "bodyRaw" => <<<EOD
<blockquote class="Quote" rel="$usernameNoSpace">abc</blockquote>

@$usernameNoSpace test

<a href="$profileUrlNoSpace">$profileUrlNoSpace</a>
EOD
                            ,
                            "format" => "html",
                            "dateInserted" => "2023-01-27T20:53:01+00:00",
                            "insertUser" => [
                                "userID" => 16,
                                "name" => self::USERNAME_NO_SPACE,
                                "url" => "https://dev.vanilla.localhost/profile/UserToAnonymize",
                                "photoUrl" =>
                                    "https://dev.vanilla.localhost/applications/dashboard/design/images/defaulticon.png",
                                "dateLastActive" => date("c", $this->mockedTimeStamp),
                                "banned" => 0,
                                "punished" => 0,
                                "private" => false,
                            ],
                            "url" => "https://dev.vanilla.localhost/discussion/1362/test",
                            "embedType" => "quote",
                            "name" => "test",
                        ],
                        "children" => [
                            [
                                "text" => "",
                            ],
                        ],
                    ],
                ]),
                json_encode([
                    [
                        "type" => "rich_embed_card",
                        "dataSourceType" => "url",
                        "url" => "https://dev.vanilla.localhost/discussion/1362/test",
                        "embedData" => [
                            "recordID" => 1362,
                            "recordType" => "discussion",
                            "body" => <<<EOT
<blockquote class="Quote blockquote">abc</blockquote>
<a href="$profileUrlAnonymize" rel="nofollow">@$usernameAnonymize</a> test<br><br><a rel="nofollow" href="$profileUrlAnonymize">$profileUrlAnonymize</a>
EOT
                            ,
                            "bodyRaw" => <<<EOT
<blockquote class="Quote" rel="$usernameAnonymize">abc</blockquote>

@"$usernameAnonymize" test

<a href="$profileUrlAnonymize">$profileUrlAnonymize</a>
EOT
                            ,
                            "format" => "html",
                            "dateInserted" => "2023-01-27T20:53:01+00:00",
                            "insertUser" => [
                                "userID" => 0,
                                "name" => "unknown",
                                "url" => $baseUrl . "/profile/",
                                "photoUrl" => $baseUrl . "/applications/dashboard/design/images/defaulticon.png",
                                "dateLastActive" => date("c", $this->mockedTimeStamp),
                                "banned" => 0,
                                "punished" => 0,
                                "private" => false,
                            ],
                            "displayOptions" => [
                                "showUserLabel" => false,
                                "showCompactUserInfo" => true,
                                "showDiscussionLink" => true,
                                "showPostLink" => true,
                                "showCategoryLink" => false,
                                "renderFullContent" => false,
                                "expandByDefault" => false,
                            ],
                            "url" => "https://dev.vanilla.localhost/discussion/1362/test",
                            "embedType" => "quote",
                            "name" => "test",
                        ],
                        "children" => [
                            [
                                "text" => "",
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
}
