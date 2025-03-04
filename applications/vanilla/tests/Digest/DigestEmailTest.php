<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Forum\Digest;

use Vanilla\Forum\Digest\DigestEmail;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class DigestEmailTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    protected $digestEmail;

    public function setUp(): void
    {
        parent::setUp();
        $this->digestEmail = $this->container()->get(DigestEmail::class);
    }
    /**
     * Test Unsubscribelink
     *
     * @return void
     */
    public function testUnsubscribe()
    {
        $category = $this->createCategory();
        $user = $this->createUser();
        $htmlContent = "<html><head><title> Weekly Digest</title></head>
                        <body>
                        <h1>Trending Post</h1>
                        <p><a href='*/unsubscribe_{$category["categoryID"]}/*'>Unsubscribe</a></p>
                        </body>";
        $textContent = "Trending Post \n\n unsubscribe:*/unsubscribe_{$category["categoryID"]}/*";
        $this->digestEmail->setHtmlContent($htmlContent);
        $this->digestEmail->setTextContent($textContent);

        $this->digestEmail->mergeCategoryUnSubscribe($user, [$category["categoryID"]]);
        $mergedHtmlContent = $this->digestEmail->getHtmlContent();

        // Testing that we're using "https" in our unsubscribe links. The getBaseUrl() method returns a string starting with "https".
        $this->assertStringContainsString("{$this->getBaseUrl()}/unsubscribe", $mergedHtmlContent);

        $this->assertStringNotContainsString("*/unsubscribe_{$category["categoryID"]}/*", $mergedHtmlContent);
        $this->assertNotEquals($htmlContent, $mergedHtmlContent);

        $mergedTextContent = $this->digestEmail->getTextContent();
        $this->assertStringNotContainsString("*/unsubscribe_{$category["categoryID"]}/*", $mergedTextContent);
    }

    /**
     * Test to validate email digest introduction and footer.
     *
     * @return void
     */
    public function testDigestIntroductionAndFooter()
    {
        $this->assertEmpty($this->digestEmail->getFooterContent());
        $this->assertEmpty($this->digestEmail->getIntroductionContentForDigest());

        $this->runWithConfig(
            [
                "Garden.Digest.Introduction" => '[{"type":"p","children":[{"text":"This is a test introduction."}]}]',
                "Garden.Digest.Footer" => '[{"type":"p","children":[{"text":"This is a test footer."}]}]',
            ],
            function () {
                $this->digestEmail->setFormat("html");
                $this->assertEquals(
                    "<p>This is a test introduction.</p>",
                    $this->digestEmail->getIntroductionContentForDigest()
                );
                $this->assertEquals("<p>This is a test footer.</p>", $this->digestEmail->getFooterContent());

                $this->digestEmail->setFormat("text");
                $this->assertEquals(
                    "This is a test introduction.",
                    $this->digestEmail->getIntroductionContentForDigest()
                );
                $this->assertEquals("This is a test footer.", $this->digestEmail->getFooterContent());
            }
        );
    }
}
