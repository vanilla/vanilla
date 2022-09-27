<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2021 Higher Logic.
 * @license Proprietary
 */

namespace VanillaTests\Addons\JsConnect;

use Gdn_Configuration;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the pockets model.
 */
class JsconnectPLuginTest extends SiteTestCase
{
    use ExpectExceptionTrait;

    protected const CLIENT_ID_SINGLE = "single";

    public static $addons = ["jsconnect"];

    /**
     * @var \Gdn_AuthenticationProviderModel
     */
    private $providerModel;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (
            \OAuth2Plugin $oauth2Plugin,
            \Gdn_AuthenticationProviderModel $authenticationProviderModel
        ) {
            $this->providerModel = $authenticationProviderModel;
        });
    }

    /**
     * Test getting getProvider.
     *
     */
    public function testGetProviderNotConfigured()
    {
        /** @var array Provider */
        $provider = \JsConnectPlugin::getProvider("facebook");
        $this->assertSame(false, $provider);
    }

    /**
     * Test getting getProvider.
     *
     */
    public function testGetProviderConfiguredNotEntered()
    {
        $cf = static::container()->get(Gdn_Configuration::class);

        $cf->set("Plugins.Facebook.ApplicationID", "something");
        $cf->set("Plugins.Facebook.Secret", "somethingElse");

        /** @var array Provider */
        $provider = \JsConnectPlugin::getProvider("facebook");
        $this->assertSame(false, $provider);
    }
}
