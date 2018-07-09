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

    /**
     * Test nonce is issued and verified
     */
    public function testIssueAndVerify() {
        $model = new UserAuthenticationNonceModel('hhh');

        $nonce = $model->issue();
        $this->assertEquals(true,  $model->verify($nonce));
    }

    /**
     * That a nonce can be consumed.
     *
     * @throws \Gdn_UserException
     */
    public function testConsume() {
        $model = new UserAuthenticationNonceModel('hhh');
        $issuedNonce = $model->issue();
        $model->consume($issuedNonce);
        $consumedNonce = $model->getNonce($issuedNonce);

        $this->assertEquals("1971-01-01 00:00:01", $consumedNonce['Timestamp']);
    }


}
