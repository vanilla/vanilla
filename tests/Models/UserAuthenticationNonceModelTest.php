<?php
/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use UserAuthenticationNonceModel;
use VanillaTests\SiteTestTrait;

/**
 * Test the {@link UserAuthenticationNonceModel}.
 */
class UserAuthenticationNonceModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait;

    /**
     * Test nonce is issued and verified
     */
    public function testIssueAndVerify() {
        $model = new UserAuthenticationNonceModel('hhh');
        $nonce = $model->issue();
        $this->assertEquals(true, $model->verify($nonce));
    }

    /**
     * That a nonce can be consumed.
     */
    public function testConsume() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nonce was already used.');

        $model = new UserAuthenticationNonceModel('hhh');
        $issuedNonce = $model->issue();
        $model->consume($issuedNonce);
        $consumedNonce = $model->getID($issuedNonce, DATASET_TYPE_ARRAY);
        $model->verify($consumedNonce['Nonce'], true, true);
    }
}
