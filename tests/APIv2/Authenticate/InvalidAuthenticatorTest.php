<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2\Authenticate;

use Exception;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test the /api/v2/authenticate endpoints.
 */
class InvalidAuthenticatorTest extends AbstractAPIv2Test {

    private $baseUrl = '/authenticate';

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'.\AuthenticateApiController::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->startSessionOnSetup(false);
        parent::setUp();
    }

    /**
     * Test POST /authenticate with an invalid authenticator
     *
     * @expectedException Exception
     * @expectedExceptionMessage Authenticator not found.
     */
    public function testAuthenticate() {
        $postData = [
            'authenticate' => [
                'authenticatorType' => 'invalid',
                'authenticatorID' => 'invalid',
            ],
        ];

        $this->api()->post(
            $this->baseUrl,
            $postData
        );
    }
}
