<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Exception\ForbiddenException;
use Vanilla\Exception\PermissionException;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\Fixtures\MockSiteMetaExtra;
use VanillaTests\Fixtures\PageFixture;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests of Vanilla's base page class.
 */
class PageTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /**
     * Test that links get added properly.
     */
    public function testLinkTags()
    {
        /** @var PageFixture $fixture */
        $fixture = self::container()->get(PageFixture::class);

        $fixture->addLinkTag(["rel" => "isLink"]);
        $fixture->addMetaTag(["type" => "isMeta"]);
        $fixture->addOpenGraphTag("og:isOg", "ogContent");

        $result = $fixture->render()->getData();

        $dom = new \DOMDocument();
        @$dom->loadHTML($result);

        $xpath = new \DOMXPath($dom);
        $head = $xpath->query("head")->item(0);

        // Link tag
        $link = $xpath->query("//link[@rel='isLink']", $head);
        $this->assertEquals(1, $link->count());

        // Meta tags
        $meta = $xpath->query("//meta[@type='isMeta']", $head);
        $this->assertEquals(1, $meta->count());

        $meta = $xpath->query("//meta[@property='og:isOg']", $head);
        $this->assertEquals(1, $meta->count());
    }

    /**
     * Test that extra metas can be applied.
     */
    public function testExtraMeta()
    {
        /** @var PageFixture $fixture */
        $fixture = self::container()->get(PageFixture::class);
        $fixture->addSiteMetaExtra(new MockSiteMetaExtra(["hello" => ["world" => "foo"]]));
        $result = $fixture->render()->getData();

        $doc = new TestHtmlDocument($result);
        $scriptTags = $doc->queryXPath("//script[contains(text(),\"gdn\")]");
        $lastInlineScript = $scriptTags->item($scriptTags->count() - 1)->textContent;

        $helloWorldJs = '"hello":{"world":"foo"}';
        $this->assertStringContainsString($helloWorldJs, $lastInlineScript);
    }

    /**
     * Test the permission calls on the page.
     *
     * @param array $permissions
     * @param array $args
     * @param string|null $exception
     *
     * @dataProvider providePermissions
     */
    public function testPermissionCheck(array $permissions, array $args, string $exception = null)
    {
        /** @var PageFixture $fixture */
        $fixture = self::container()->get(PageFixture::class);
        $this->runWithPermissions(function () use ($fixture, $exception, $args) {
            if ($exception) {
                $this->expectException($exception);
            }
            $fixture->permission(...$args);

            // We expected  "no exception'. Pass the test.
            $this->assertTrue(true);
        }, $permissions);
    }

    /**
     * @return array[]
     */
    public function providePermissions(): array
    {
        return [
            "has one" => [
                [
                    "site.manage" => true,
                ],
                ["Garden.Settings.Manage"],
            ],
            "missing one" => [
                [
                    "site.manage" => false,
                ],
                ["Garden.Settings.Manage"],
                ForbiddenException::class,
            ],
            "has two" => [
                [
                    "site.manage" => true,
                    "community.manage" => true,
                ],
                [["Garden.Settings.Manage", "Garden.Community.Manage"]],
            ],
            "has one of two" => [
                [
                    "site.manage" => true,
                    "community.manage" => false,
                ],
                [["Garden.Settings.Manage", "Garden.Community.Manage"]],
            ],
            "has none of two" => [
                [
                    "site.manage" => false,
                    "community.manage" => false,
                ],
                [["Garden.Settings.Manage", "Garden.Community.Manage"]],
                ForbiddenException::class,
            ],
        ];
    }

    /**
     * Test permission checking with an id.
     */
    public function testPermissionCategory()
    {
        $category = $this->createCategory();
        $categoryPerm = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID]);

        /** @var PageFixture $fixture */
        $fixture = self::container()->get(PageFixture::class);
        $fixture->permission("Vanilla.Discussions.Add", $category["categoryID"]);

        $fixture->permission("Vanilla.Discussions.View", $categoryPerm["categoryID"]);

        $normalUser = $this->createUser();
        $this->runWithUser(function () use ($fixture, $categoryPerm) {
            $this->expectExceptionCode(403);
            $fixture->permission("Vanilla.Discussions.View", $categoryPerm["categoryID"]);
        }, $normalUser);
    }
}
