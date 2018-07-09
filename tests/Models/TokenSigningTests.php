<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\Fixtures\TokenModel;

/**
 * Test the {@link TokenModel}.
 */
class TokenSigningTests extends SharedBootstrapTestCase {
    use SiteTestTrait;

    public function testVerifyRandomTokenSignature() {
        $model = new TokenModel();
        $model->tokenIdentifier ='nonce';
        $token = $model->randomSignedToken();

        $this->assertEquals(true, $model->verifyTokenSignature($token, $model->tokenIdentifier));
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

        $model->verifyTokenSignature($token, $model->tokenIdentifier, true);
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
        $model->verifyTokenSignature($token, $model->tokenIdentifier, true);
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
        $model->verifyTokenSignature($token, $model->tokenIdentifier, true);
    }


}
