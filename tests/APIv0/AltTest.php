<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0;

use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests an alternate install method.
 */
class AltTest extends AbstractAPIv2Test
{
    public static $addons = ["stubcontent"];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useLegacyLayouts();
    }

    /**
     * Test an alternate install method.
     *
     * @large
     */
    public function testAltInstall()
    {
        $this->doAltInstallWithUpdateToken(false, "xkcd", "");
    }

    /**
     * Test an ALT install with a valid update token.
     *
     * @large
     */
    public function testAltInstallWithUpdateToken()
    {
        $this->doAltInstallWithUpdateToken(true, "xkcd", "xkcd");
    }

    /**
     * Test an ALT install with no update token.
     *
     * @large
     */
    public function testAltInstallWithNoUpdateToken()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(401);
        $this->doAltInstallWithUpdateToken(true, "xkcd", "");
    }

    /**
     * Run an alt install with optional update tokens.
     *
     * @param bool $enabled Whether the update token feature should be enabled.
     * @param string $updateToken The update token to use.
     * @param string $postUpdateToken The update token to post during `utility/update`.
     * @param bool $retried Whether this is a retry or not.
     */
    private function doAltInstallWithUpdateToken(
        bool $enabled,
        string $updateToken,
        string $postUpdateToken,
        bool $retried = false
    ) {
        $apiv0 = new E2ETestClient();

        $apiv0->uninstall();
        $apiv0->createDatabase();

        $config = $this->getBaseConfig($apiv0);
        $config["Feature"]["updateTokens"]["Enabled"] = $enabled;
        $config["Garden"]["UpdateToken"] = $updateToken;
        $config["Debug"] = true;

        $apiv0->saveToConfig($config);
        $r = $apiv0->post("/utility/update.json", ["updateToken" => $postUpdateToken])->getBody();
        $this->assertEquals(
            true,
            $r["Success"],
            "Site failed to install properly " . json_encode($r, JSON_PRETTY_PRINT)
        );
        $apiv0->saveToConfig([
            "Garden.Installed" => true,
            "Feature.customLayout.home.Enabled" => false,
            "Feature.customLayout.discussionList.Enabled" => false,
            "Feature.customLayout.categoryList.Enabled" => false,
        ]);

        // Do a simple get to make sure there isn't an error.
        $data = $apiv0->get("/discussions.json")->getBody();
        $this->assertIsArray($data);
        /** @var \Gdn_DataSet $discussions */
        $discussions = $data["Discussions"] ?? null;
        $this->assertIsArray($discussions, "Could not find discussions in: " . json_encode($data, JSON_PRETTY_PRINT));

        if (count($discussions) === 3) {
            $this->assertCount(3, $discussions);
        } elseif (!$retried) {
            // This test is notoriously flaky.
            // If this failed, we are just going to try again. We are still unsure what causes the failure.
            $this->doAltInstallWithUpdateToken($enabled, $updateToken, $postUpdateToken, true);
        } else {
            $wholeConfig = \Gdn::config()->get(".");
            /** @var \DiscussionModel $discussionModel */
            $discussionModel = $this->container()->get(\DiscussionModel::class);
            $allDiscussions = $discussionModel->getWhere(["Announce" => false])->resultArray();
            $message =
                "Alt Install failed twice.\n" .
                "Dumping Config:\n" .
                json_encode($wholeConfig, JSON_PRETTY_PRINT) .
                "\n" .
                "Dumping Discussion Table:\n" .
                json_encode($allDiscussions, JSON_PRETTY_PRINT);

            $this->fail($message);
        }
    }

    /**
     * Get the config to be applied to the site before update.
     *
     * @param E2ETestClient $apiv0
     */
    private function getBaseConfig(E2ETestClient $apiv0)
    {
        $config = [
            "Database" => [
                "Host" => $apiv0->getDbHost(),
                "Name" => $apiv0->getDbName(),
                "User" => $apiv0->getDbUser(),
                "Password" => $apiv0->getDbPassword(),
            ],
            "EnabledApplications" => [
                "Vanilla" => "vanilla",
                "Conversations" => "conversations",
            ],
            "EnabledPlugins" => [
                "vanillicon" => true,
                "Facebook" => true,
                "Twitter" => true,
                "Akismet" => true,
                "StopForumSpam" => true,
            ],
            "Garden" => [
                "Installed" => null, // Important to bypass the redirect to /dashboard/setup. False would not do here.
                "Title" => get_called_class(),
                "Domain" => parse_url($apiv0->getBaseUrl(), PHP_URL_HOST),
                "Cookie" => [
                    "Salt" => "salt",
                    "Name" => "vf_" . strtolower(get_called_class()) . "_ENDTX",
                    "Domain" => "",
                ],
                "Email" => [
                    "SupportAddress" => "noreply@vanilla.test",
                    "SupportName" => get_called_class(),
                ],
            ],
        ];

        return $config;
    }
}
