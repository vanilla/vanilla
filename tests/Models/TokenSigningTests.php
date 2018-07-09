<?php
/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\Fixtures\TokenModel;

/**
 * Test the {@link TokenModel}.
 * Used to test the token generation and signing utility methods.
 */
class TokenSigningTests extends SharedBootstrapTestCase {
    use SiteTestTrait;

    /**
     * Tests random token generation and signing.
     */
    public function testVerifyRandomTokenSignature() {
        $model = new TokenModel();
        $model->tokenIdentifier ='nonce';
        $token = $model->randomSignedToken();
        $this->assertEquals(true, $model->verifyTokenSignature($token, $model->tokenIdentifier, true));
    }

    /**
     * An expired token shouldn't verify.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Your nonce has expired.
     */
    public function testExpiryDate() {
        $model = new TokenModel();
        $model->tokenIdentifier ='nonce';
        $token = $model->randomSignedToken('last month');
        $this->assertEquals(false, $model->verifyTokenSignature($token, $model->tokenIdentifier, true));
    }

    /**
     * An altered token signature shouldn't verify.
     *
     * @expectedException \Exception
     * $expectedExceptionMessage Invalid signature.
     */
    public function testBadSignature() {
        $model = new TokenModel();
        $model->tokenIdentifier ='nonce';
        $token = $model->randomSignedToken().'!';
        $this->assertEquals(false, $model->verifyTokenSignature($token, $model->tokenIdentifier, true));
    }

    /**
     * A nonsense token shouldn't verify.
     *
     * @expectedException \Exception
     */
    public function testBadToken() {
        $model = new TokenModel();
        $model->tokenIdentifier = 'nonce';
        $token = 'a.b.c';
        $this->assertEquals(false, $model->verifyTokenSignature($token, $model->tokenIdentifier, true));
    }

}
