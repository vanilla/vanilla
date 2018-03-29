<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2\Authenticate;

use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test the /api/v2/authenticate endpoints.
 */
class InvalidAuthenticatorTest extends AbstractAPIv2Test {

    private $baseUrl = '/authenticate';

    public function setUp() {
        $this->startSessionOnSetup(false);
        parent::setUp();
    }

    /**
     * Test POST /authenticate with an invalid authenticator
     *
     * @expectedException \Exception
     * @expectedExceptionMessage invalidAuthenticator not found.
     */
    public function testAuthenticate() {
        $postData = [
            'authenticatorType' => 'invalid',
            'authenticatorID' => '',
        ];

        $this->api()->post(
            $this->baseUrl,
            $postData
        );
    }
}
