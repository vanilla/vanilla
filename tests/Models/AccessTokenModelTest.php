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
class AccessTokenModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait;

    /**
     * An access token should verify after being issued.
     */
    public function testIssueAndVerify() {
        $model = new AccessTokenModel('sss');
        $token = $model->issue(1);
        $this->assertEquals(1, $model->verify($token)['UserID']);

        return $token;
    }

    /**
     * Test revoking a token.
     *
     * @param string $token A valid access token to revoke.
     * @depends testIssueAndVerify
     */
    public function testRevoke($token) {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Your access token was revoked.');

        $model = new AccessTokenModel('sss');
        $this->assertTrue($model->revoke($token));
        $model->verify($token, true);
    }

    /**
     * A deleted token shouldn't verify.
     */
    public function testVerifyDeletedToken() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access token not found.');

        $model = new AccessTokenModel('sss');
        $token = $model->issue(1);
        $model->verify($token, true);
        $row = $model->getToken($model->trim($token));
        $id = $row['AccessTokenID'];
        $model->deleteID($id);
        $model->verify($token, true);
    }

    /**
     * Test that our config saved tokens work correctly.
     */
    public function testEnsureSingleSystemToken() {
        $model = new AccessTokenModel('sss');

        $model->ensureSingleSystemToken();
        $initialConfToken = \Gdn::config()->get(AccessTokenModel::CONFIG_SYSTEM_TOKEN);
        $this->assertNotFalse($model->verify($initialConfToken));

        // Run again, should revoke the first token and save a new one.
        $model->ensureSingleSystemToken();
        $secondConfToken = \Gdn::config()->get(AccessTokenModel::CONFIG_SYSTEM_TOKEN);
        $this->assertFalse($model->verify($initialConfToken));
        $this->assertNotFalse($model->verify($secondConfToken));
    }
}
