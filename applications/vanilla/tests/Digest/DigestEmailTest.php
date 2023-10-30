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

    /**
     * Test Unsubscribelink
     *
     * @return void
     */
    public function testUnsubscribe()
    {
        $digestEmail = $this->container()->get(DigestEmail::class);
        $category = $this->createCategory();
        $user = $this->createUser();
        $htmlContent = "<html><head><title> Weekly Digest</title></head>
                        <body>
                        <h1>Trending Post</h1>
                        <p><a href='*/unsubscribe_{$category["categoryID"]}/*'>Unsubscribe</a></p>
                        </body>";
        $textContent = "Trending Post \n\n unsubscribe:*/unsubscribe_{$category["categoryID"]}/*";
        $digestEmail->setHtmlContent($htmlContent);
        $digestEmail->setTextContent($textContent);

        $digestEmail->mergeCategoryUnSubscribe($user, [$category["categoryID"]]);
        $mergedHtmlContent = $digestEmail->getHtmlContent();
        $this->assertStringNotContainsString("*/unsubscribe_{$category["categoryID"]}/*", $mergedHtmlContent);
        $this->assertNotEquals($htmlContent, $mergedHtmlContent);

        $mergedTextContent = $digestEmail->getTextContent();
        $this->assertStringNotContainsString("*/unsubscribe_{$category["categoryID"]}/*", $mergedTextContent);
    }
}
