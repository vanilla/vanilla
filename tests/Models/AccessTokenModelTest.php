<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Models;

use AccessTokenModel;
use VanillaTests\SiteTestTrait;

/**
 * Test the {@link AccessTokenModel}.
 */
class AccessTokenModelTest extends \PHPUnit_Framework_TestCase {
    use SiteTestTrait;

    /**
     * A newly issued token should verify.
     */
    public function testVerifyRandomTokenSignature() {
        $model = new AccessTokenModel('sss');

        $token = $model->randomSignedToken();
        $this->assertTrue($model->verifyTokenSignature($token));
    }

    /**
     * An expired token shouldn't verify.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Your access token has expired.
     */
    public function testExpiryDate() {
        $model = new AccessTokenModel('sss');

        $token = $model->randomSignedToken('last month');
        $this->assertFalse($model->verifyTokenSignature($token));
        $this->assertFalse($model->verifyTokenSignature($token, true));
    }

    /**
     * An altered token signature shouldn't verify.
     *
     * @expectedException \Exception
     * $expectedExceptionMessage Invalid signature.
     */
    public function testBadSignature() {
        $model = new AccessTokenModel('sss');

        $token = $model->randomSignedToken().'!';
        $this->assertFalse($model->verifyTokenSignature($token));
        $this->assertFalse($model->verifyTokenSignature($token, true));
    }

    /**
     * A nonsense token shouldn't verify.
     *
     * @expectedException \Exception
     */
    public function testBadToken() {
        $model = new AccessTokenModel('sss');

        $token = 'a.b.c';
        $this->assertFalse($model->verifyTokenSignature($token));
        $this->assertFalse($model->verifyTokenSignature($token, true));
    }

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
        $model->deleteID($model->trim($token));
        $model->verify($token, true);
    }
}
