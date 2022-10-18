<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2021 Higher Logic.
 * @license Proprietary
 */

namespace VanillaTests\Addons\JsConnect;

use Garden\Schema\ValidationException;
use Gdn_Configuration;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the pockets model.
 */
class JsConnectPluginTest extends SiteTestCase
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
        \Gdn::sql()->truncate("UserAuthenticationProvider");
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

    /**
     * Basic tests of adding a jsconnect provider
     * @return void
     * @dataProvider JsConnectDataProvider
     */
    public function testAddJsConnectProvider(
        array $data,
        ?string $expectException = null,
        ?string $expectExceptionMessage = null
    ) {
        if ($expectException) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage($expectExceptionMessage);
        }
        $this->bessy()->post("/settings/jsconnect/addedit", $data);

        $count = \Gdn::sql()->getCount("UserAuthenticationProvider");
        $this->assertSame(1, $count);
    }

    /**
     * Basic tests for updating a jsconnect provider
     *
     * @return void
     * @dataProvider JsConnectDataProvider
     */
    public function testUpdateJsConnectProvider(
        array $data,
        ?string $expectException = null,
        ?string $expectExceptionMessage = null
    ) {
        $valid = $this->getValidJsConnectData();
        $this->bessy()->post("/settings/jsconnect/addedit", $valid);

        if ($expectException) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage($expectExceptionMessage);
        }

        $this->bessy()->post("/settings/jsconnect/addedit?client_id={$valid["AuthenticationKey"]}", $data);

        $rows = \Gdn::sql()
            ->get("UserAuthenticationProvider")
            ->resultArray();
        $this->assertCount(1, $rows);
        $this->assertSame($data["Name"], $rows[0]["Name"]);
        $this->assertSame($data["AuthenticationKey"], $rows[0]["AuthenticationKey"]);
        $this->assertSame($data["AssociationSecret"], $rows[0]["AssociationSecret"]);
    }

    /**
     * Provides data for adding jsconnect providers
     *
     * @return \Generator
     */
    public function JsConnectDataProvider(): \Generator
    {
        $valid = $this->getValidJsConnectData();

        yield "Valid data" => [$valid];
        yield "Valid data 2" => [
            [
                "AuthenticationKey" => "AuthenticationKey2",
                "AssociationSecret" => "AssociationSecret2",
                "Name" => "Example2",
                "AuthenticateUrl" => "https://example.com/sso2",
                "SignInUrl" => "https://example.com/signin2",
                "RegisterUrl" => "https://example.com/register2",
                "SignOutUrl" => "https://example.com/signout2",
                "Protocol" => "v3",
                "HashType" => "md5",
                "Save" => "Save",
                "DeliveryType" => "VIEW",
                "DeliveryMethod" => "JSON",
            ],
        ];
        yield "Missing AuthenticationKey" => [
            array_merge($valid, ["AuthenticationKey" => ""]),
            ValidationException::class,
            "Client ID is required.",
        ];
        yield "Missing AssociationSecret" => [
            array_merge($valid, ["AssociationSecret" => ""]),
            ValidationException::class,
            "Secret is required.",
        ];
        yield "Missing AuthenticateUrl" => [
            array_merge($valid, ["AuthenticateUrl" => ""]),
            ValidationException::class,
            "Authentication Url is required.",
        ];
    }

    /**
     * Returns a set of valid jsconnect data
     *
     * @return string[]
     */
    public function getValidJsConnectData(): array
    {
        return [
            "AuthenticationKey" => "AuthenticationKey",
            "AssociationSecret" => "AssociationSecret",
            "Name" => "Example",
            "AuthenticateUrl" => "https://example.com/sso",
            "SignInUrl" => "https://example.com/signin",
            "RegisterUrl" => "https://example.com/register",
            "SignOutUrl" => "https://example.com/signout",
            "Protocol" => "v3",
            "HashType" => "md5",
            "Save" => "Save",
            "DeliveryType" => "VIEW",
            "DeliveryMethod" => "JSON",
        ];
    }
}
