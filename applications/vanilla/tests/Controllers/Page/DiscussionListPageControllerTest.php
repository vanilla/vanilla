<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Controllers\Page;

use Garden\Web\Dispatcher;
use Garden\Web\Exception\ResponseException;
use Garden\Web\Redirect;
use Vanilla\FeatureFlagHelper;
use VanillaTests\Fixtures\Request;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the `DiscussionListPageController` class.
 */
class DiscussionListPageControllerTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    public static $addons = ["vanilla", "QnA", "resolved2"];

    /**
     * @inheritDoc
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();
        \Gdn::config()->saveToConfig("Feature.customLayout.discussionList.Enabled", true);
        FeatureFlagHelper::clearCache();
    }

    /**
     * Smoke test of custom layout discussion page
     *
     * @throws \Exception Base exception class for all possible exceptions.
     */
    public function testCustomLayoutRecentDiscussions()
    {
        $dispatcher = $this->container()->get(Dispatcher::class);

        // Test basic request
        $data = $dispatcher->dispatch(new Request("/discussions"));
        $response = $data->asHttpResponse();
        $this->assertSame(200, $response->getStatusCode());

        // Test request with valid type parameter returns 200 response
        $data = $dispatcher->dispatch(new Request("/discussions?type=discussion"));
        $response = $data->asHttpResponse();
        $this->assertSame(200, $response->getStatusCode());

        // Test request with invalid type parameter returns non-200 response
        $data = $dispatcher->dispatch(new Request("/discussions?type=invalidtype"));
        $response = $data->asHttpResponse();
        $this->assertNotSame(200, $response->getStatusCode());

        // Test request with invalid permission returns 403 response
        $this->runWithPermissions(
            function () use ($dispatcher) {
                $data = $dispatcher->dispatch(new Request("/discussions"));
                $response = $data->asHttpResponse();
                $this->assertSame(403, $response->getStatusCode());
            },
            [],
            $this->categoryPermission(-1, ["discussions.view" => false])
        );
    }

    /**
     *  Test redirects to the custom layout discussion page
     *
     * @param string $startUrl
     * @param string $redirectUrl
     * @dataProvider redirectUrlProvider
     */
    public function testDiscussionsRedirects(string $startUrl, string $redirectUrl)
    {
        try {
            $this->bessy()->get($startUrl);
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $this->assertInstanceOf(Redirect::class, $response);
            $this->assertSame(302, $response->getStatus());
            $this->assertStringEndsWith($redirectUrl, $response->getMeta("HTTP_LOCATION"));
        }
    }

    /**
     * Provider for testDiscussionsRedirects
     *
     * @return string[][]
     */
    public function redirectUrlProvider(): array
    {
        return [
            "redirect /discussions/unresolved" => ["/discussions/unresolved", "/discussions?status=unresolved"],
            "redirect /discussions/unanswered" => [
                "/discussions/unanswered",
                "/discussions?type=question&status=unanswered",
            ],
        ];
    }
}
