<?php
/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\Fixtures\TokenTestingModel;

/**
 * Test the {@link TokenSigningTrait}.
 * Used to test the token generation and signing utility methods.
 */
class TokenSigningTraitTest extends SharedBootstrapTestCase {
    use SiteTestTrait;

    /**
     * Tests random token generation and signing.
     */
    public function testVerifyRandomTokenSignature() {
        $model = new TokenTestingModel();
        $token = $model->signToken($model->randomToken(), '2 months');
        $this->assertEquals(true, $model->verifyTokenSignature($token, true));
    }

    /**
     * An expired token shouldn't verify.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Your nonce has expired.
     */
    public function testExpiryDate() {
        $model = new TokenTestingModel();
        $token = $model->signToken($model->randomToken(), 'last year');
        $model->verifyTokenSignature($token, true);
    }

    /**
     * An altered token signature shouldn't verify.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Your nonce has an invalid signature.
     */
    public function testBadSignature() {
        $model = new TokenTestingModel();
        $token = $model->signToken($model->randomToken(), '2 months').'!';
        $model->verifyTokenSignature($token, true);
    }

    /**
     * A nonsense token shouldn't verify.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Your nonce missing parts.
     */
    public function testBadToken() {
        $model = new TokenTestingModel();
        $token = 'hr.df.ee';
        $model->verifyTokenSignature($token, true);
    }
}
