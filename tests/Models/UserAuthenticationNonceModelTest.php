<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use UserAuthenticationNonceModel;
use VanillaTests\SiteTestTrait;

/**
 * Test the {@link AccessTokenModel}.
 */
class UserAuthenticationNonceModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait;

//    /**
//     * A newly issued token should verify.
//     */
//    public function testVerifyRandomTokenSignature() {
//        $model = new AccessTokenModel('sss');
//
//        $token = $model->randomSignedToken();
//        $this->assertTrue($model->verifyTokenSignature($token, 'access token'));
//    }
//
//    /**
//     * An expired token shouldn't verify.
//     *
//     * @expectedException \Exception
//     * @expectedExceptionMessage Your access token has expired.
//     */
//    public function testExpiryDate() {
//        $model = new AccessTokenModel('sss');
//
//        $token = $model->randomSignedToken('last month');
//        $this->assertFalse($model->verifyTokenSignature($token, 'access token', true));
//        $this->assertFalse($model->verifyTokenSignature($token, 'access token', true));
//    }
//
//    /**
//     * An altered token signature shouldn't verify.
//     *
//     * @expectedException \Exception
//     * $expectedExceptionMessage Invalid signature.
//     */
//    public function testBadSignature() {
//        $model = new AccessTokenModel('sss');
//
//        $token = $model->randomSignedToken().'!';
//        $this->assertFalse($model->verifyTokenSignature($token,'access token', true));
//        $this->assertFalse($model->verifyTokenSignature($token,'access token', true));
//    }
//
//    /**
//     * A nonsense token shouldn't verify.
//     *
//     * @expectedException \Exception
//     */
//    public function testBadToken() {
//        $model = new AccessTokenModel('sss');
//
//        $token = 'a.b.c';
//        $this->assertFalse($model->verifyTokenSignature($token,'access token', true));
//        $this->assertFalse($model->verifyTokenSignature($token,'access token', true));
//    }

    /**
     * Test nonce is issued and verified
     */
    public function testIssueAndVerify() {
        $model = new UserAuthenticationNonceModel('hhh');

        $nonce = $model->issue();
        $this->assertEquals(true,  $model->verify($nonce));
    }

    public function testConsume() {
        $model = new UserAuthenticationNonceModel('hhh');
        $issuedNonce = $model->issue();
        $model->consume($issuedNonce);
        $consumedNonce = $model->getNonce($issuedNonce);

        $this->assertEquals("1971-12-31 01:01:01", $consumedNonce['Timestamp']);
    }


}
