<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\SharedBootstrapTestCase;
use AccessTokenModel;
use VanillaTests\SiteTestTrait;

/**
 * Test the {@link AccessTokenModel}.
 */
class AccessTokenModelTest extends SharedBootstrapTestCase
{
    use SiteTestTrait;

    /**
     * An access token should verify after being issued.
     */
    public function testIssueAndVerify()
    {
        $model = new AccessTokenModel("sss");
        $token = $model->issue(1);
        $this->assertEquals(1, $model->verify($token)["UserID"]);

        return $token;
    }

    /**
     * Test issuing and verifying tokens using different config settings
     *
     * @return void
     * @throws \Gdn_UserException
     * @dataProvider provideIssueAndVerifyUsingSaltData
     */
    public function testIssueAndVerifyUsingSalt(array $issueConfig, array $verifyConfig, int $expectedVersion)
    {
        $token = $this->runWithConfig($issueConfig, function () use ($expectedVersion) {
            $model = new AccessTokenModel();
            $this->assertSame($expectedVersion, $model->getVersion());
            return $model->issue(1);
        });

        $this->runWithConfig($verifyConfig, function () use ($token) {
            $model = new AccessTokenModel();
            $this->assertEquals(1, $model->verify($token)["UserID"]);
        });
    }

    /**
     * Provides config data for testIssueAndVerifyUsingSalt
     *
     * @return array
     */
    public function provideIssueAndVerifyUsingSaltData(): array
    {
        return [
            "issued with version 1 and verified with Garden.Cookie.Salt" => [
                ["Garden.Cookie.Salt" => "123"],
                ["Garden.Cookie.Salt" => "123"],
                1,
            ],
            "issued with version 1 and verified with Garden.Cookie.OldSalt" => [
                ["Garden.Cookie.Salt" => "123"],
                ["Garden.Cookie.Salt" => "456", "Garden.Cookie.OldSalt" => "123"],
                1,
            ],
            "issued with version 2 and verified with Garden.Cookie.Salt" => [
                ["Garden.Cookie.Salt" => "123", "Garden.Cookie.OldSalt" => "456"],
                ["Garden.Cookie.Salt" => "123", "Garden.Cookie.OldSalt" => "456"],
                2,
            ],
        ];
    }

    /**
     * Test revoking a token.
     *
     * @param string $token A valid access token to revoke.
     * @depends testIssueAndVerify
     */
    public function testRevoke($token)
    {
        $model = new AccessTokenModel("sss");
        $row = $model->verify($token);
        $this->assertTrue($model->revoke($token));

        $tokenRow = $model->getID($row["AccessTokenID"]);
        $this->assertTrue($tokenRow["Attributes"]["revoked"], "The access token should have been marked revoked.");

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Your access token was revoked.");
        $model->verify($token, true);
    }

    /**
     * Revoking a deleted token should fail silently.
     */
    public function testRevokeDeletedToken()
    {
        $this->expectNotToPerformAssertions();
        $model = new AccessTokenModel("secret");
        $token = $model->issue(1);
        $model->verify($token, true);
        $row = $model->getToken($model->trim($token));
        $id = $row["AccessTokenID"];
        $model->deleteID($id);
        $model->revoke($token);
    }

    /**
     * A deleted token shouldn't verify.
     */
    public function testVerifyDeletedToken()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Access token not found.");

        $model = new AccessTokenModel("sss");
        $token = $model->issue(1);
        $model->verify($token, true);
        $row = $model->getToken($model->trim($token));
        $id = $row["AccessTokenID"];
        $model->deleteID($id);
        $model->verify($token, true);
    }

    /**
     * Test that our config saved tokens work correctly.
     */
    public function testEnsureSingleSystemToken()
    {
        $model = new AccessTokenModel("sss");

        $model->ensureSingleSystemToken();
        $initialConfToken = \Gdn::config()->get(AccessTokenModel::CONFIG_SYSTEM_TOKEN);
        $this->assertNotFalse($model->verify($initialConfToken));

        // Run again, should remove the token from the Config file.
        $model->ensureSingleSystemToken();
        $secondConfToken = \Gdn::config()->get(AccessTokenModel::CONFIG_SYSTEM_TOKEN);
        $this->assertNotEquals($initialConfToken, $secondConfToken);
        $this->assertNotFalse($model->verify($secondConfToken));
        $config = $this->container()->get(ConfigurationInterface::class);
        // Checking that "APIv2.SystemAccessToken" was not added to the config changes.
        $this->assertArrayNotHasKey("APIv2.SystemAccessToken", $config->ConfigChangesData);

        // Ensure that the old token is now only valid for another 6h. 5 and 7h are used for convenience.
        $initialToken = $model->verify($initialConfToken);
        $this->assertNotFalse($initialToken);
        $tresholdBefore = \Gdn_Format::toDateTime(strtotime("5 hours"));
        $this->assertGreaterThan($tresholdBefore, $initialToken["DateExpires"]);

        $tresholdAfter = \Gdn_Format::toDateTime(strtotime("7 hours"));
        $this->assertLessThan($tresholdAfter, $initialToken["DateExpires"]);
    }
}
