<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use Vanilla\Web\TwigRenderTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\SiteTestCase;
use Vanilla\Formatting\Formats\Rich2Format;

/**
 * Test rendering of the email digest template.
 */
class EmailDigestTemplateTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use TwigRenderTrait;
    use HtmlNormalizeTrait;

    public function testSimpleDigest()
    {
        $category1 = $this->createCategory([
            "name" => "Category 1",
            "urlCode" => "category-1",
            "iconUrl" => "https://us.v-cdn.net/6032207/uploads/QBZUC19U39VS/mind-brain-icon-icons-com-51079.png",
        ]);
        $category1["unsubscribeLink"] = "https://demo.vanillawip.com/unsubscribe";

        $commonExtras = [
            "expand" => "excerpt,insertUser,-body",
        ];

        $discussion1 = $this->createDiscussion(
            [
                "name" => "Discussion 1",
                "format" => "wysiwyg",
                "body" => <<<HTML
<p>A wonderful serenity has taken possession of my entire soul, like these sweet mornings of spring which I enjoy with my whole heart. I am alone, and feel the charm of existence in this</p>
<img src="https://www.higherlogic.com/wp-content/uploads/2020/06/K1_02.jpg" />
HTML
                ,
                "score" => 5,
            ],
            $commonExtras + [
                "CountComments" => 50,
            ]
        );

        $discussion2 = $this->createDiscussion(
            [
                "name" => "Discussion 2",
                "format" => "wysiwyg",
                "body" => <<<HTML
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
HTML
                ,
                "score" => 60,
            ],
            $commonExtras
        );

        $discussion3 = $this->createDiscussion(
            [
                "name" => "Discussion 3",
                "format" => "wysiwyg",
                "body" => <<<HTML
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
HTML
                ,
                "score" => 20,
            ],
            $commonExtras
        );

        $category2 = $this->createCategory([
            "name" => "Category 2",
            "urlCode" => "category-2",
        ]);
        $category2["unsubscribeLink"] = "https://demo.vanillawip.com/unsubscribe";

        $discussion4 = $this->createDiscussion(
            [
                "name" => "Discussion 4",
                "format" => "wysiwyg",
                "body" => <<<HTML
<p>Biscuit caramels pudding. Sweet roll jelly gummi bears lemon drops biscuit croissant tootsie roll danish.</p>
HTML
            ,
            ],
            $commonExtras
        );

        $discussion5 = $this->createDiscussion(
            [
                "name" => "Discussion 5",
                "format" => "wysiwyg",
                "body" => <<<HTML
<p>The quick, brown fox jumps over a lazy dog. DJs flock by when MTV ax quiz prog. Junk MTV quiz graced</p>
<img src="https://vanilla.higherlogic.com/wp-content/uploads/2023/05/image_quote_section_left.png" />
HTML
            ,
            ],
            $commonExtras
        );

        $rich2Formatter = \Gdn::getContainer()->get(Rich2Format::class);

        $rich2Footer =
            '[{"type":"p","children":[{"text":"Higher Logic Vanilla"}]},{"type":"p","children":[{"text":"1234 North Pole Lane"}]},{"type":"p","children":[{"text":"Anotherline could go in here"}]},{"type":"p","children":[{"text":"And Another Line"}]},{"type":"p","children":[{"text":"Erin, ON, Canada, N0B 1T0\\n"}]}]';

        $data = [
            "email" => [
                "siteUrl" => "https://demo.vanillawip.com",
                "title" => "This Week's Trending Posts",
                "imageUrl" => "https://www.higherlogic.com/wp-content/uploads/2020/05/higherLogic_stacked.png",
                "imageAlt" => "Vanilla Forums Digest",
                "textColor" => "#555a62",
                "backgroundColor" => "#ffffff",
                "buttonTextColor" => "#ffffff",
                "buttonBackgroundColor" => "#22a3db",
                "categories" => [
                    $category1 + [
                        "discussions" => [$discussion1, $discussion2, $discussion3],
                    ],
                    $category2 + [
                        "discussions" => [$discussion4, $discussion5],
                    ],
                ],
                "digestUnsubscribeLink" => "https://demo.vanillawip.com/unsubscribe/token",
                "footer" => $rich2Formatter->renderPlainText($rich2Footer),
            ],
        ];

        $renderedTemplate = $this->renderTwig("@vanilla/email/email-digest.twig", $data);
        $fixturePath = __DIR__ . "/EmailDigestTemplateFixture.html";
        $expectedHtml = file_get_contents($fixturePath);

        $this->assertHtmlStringEqualsHtmlString(
            $expectedHtml,
            $renderedTemplate,
            "Rendered digest did not match expected fixture."
        );

        $expectedPlaintext = file_get_contents(__DIR__ . "/EmailDigestTemplateFixture.txt");
        $renderedPlainText = $this->renderTwig("@vanilla/email/email-digest-plaintext.twig", $data);
        $this->assertEquals(trim($expectedPlaintext), trim($renderedPlainText));
    }
}
