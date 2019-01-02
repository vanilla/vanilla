<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

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
     * @expectedException \Exception
     * @expectedExceptionMessage Your access token was revoked.
     * @depends testIssueAndVerify
     */
    public function testRevoke($token) {
        $model = new AccessTokenModel('sss');
        $this->assertTrue($model->revoke($token));
        $model->verify($token, true);
    }

    /**
     * A deleted token shouldn't verify.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Access token not found.
     */
    public function testVerifyDeletedToken() {
        $model = new AccessTokenModel('sss');
        $token = $model->issue(1);
        $model->verify($token, true);
        $row = $model->getToken($model->trim($token));
        $id = $row['AccessTokenID'];
        $model->deleteID($id);
        $model->verify($token, true);
    }
}
