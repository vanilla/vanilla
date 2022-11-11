<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\JsConnect\Models;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationField;
use Vanilla\Models\AuthenticatorTypeInterface;

class JsConnectAuthenticatorTypeProvider implements AuthenticatorTypeInterface
{
    // Mapping of legacy property names to modern property names
    const INPUT_MAP = [
        "secret" => "AssociationSecret",
        "active" => "Active",
        "urls.authenticateUrl" => "AuthenticateUrl",
        "clientID" => "AuthenticationKey",
        "type" => "AuthenticationSchemeAlias",
        "default" => "IsDefault",
        "name" => "Name",
        "urls.passwordUrl" => "PasswordUrl",
        "urls.profileUrl" => "ProfileUrl",
        "urls.registerUrl" => "RegisterUrl",
        "urls.signInUrl" => "SignInUrl",
        "urls.signOutUrl" => "SignOutUrl",
        "authenticatorID" => "UserAuthenticationProviderID",
        "visible" => "Visible",
        "authenticatorConfig.Protocol" => "Protocol",
        "authenticatorConfig.HashType" => "HashType",
        "authenticatorConfig.Trusted" => "Trusted",
        "authenticatorConfig.TestMode" => "TestMode",
    ];

    /**
     * Return a schema for validating client id
     *
     * @return Schema
     */
    public function getClientIDSchema(): Schema
    {
        return Schema::parse([\JsConnectPlugin::FIELD_PROVIDER_CLIENT_ID . ":s?"])->addValidator(
            \JsConnectPlugin::FIELD_PROVIDER_CLIENT_ID,
            function (string $apiName, ValidationField $field) {
                if (preg_match("/[^a-z\d_-]/i", $apiName)) {
                    $field->addError("The client id must contain only letters, numbers and dashes.");
                }
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function getAuthenticatorType(): array
    {
        return [
            "authenticatorType" => "JsConnect",
            "name" => "JsConnect",
            "description" => "Setup a JsConnect authentication.",
            "schema" => [
                "urls:o" => [
                    "authenticateUrl:s",
                    "signInUrl:s?" => ["minLength" => 0],
                    "signOutUrl:s?" => ["minLength" => 0],
                    "registerUrl:s?" => ["minLength" => 0],
                    "passwordUrl:s?" => ["minLength" => 0],
                    "profileUrl:s?" => ["minLength" => 0],
                ],
                "authenticatorConfig:o?" => $this->getAttributesSchema(),
            ],
        ];
    }

    /**
     * Get schema for the `Attributes` field.
     *
     * @return Schema
     */
    private function getAttributesSchema(): Schema
    {
        return Schema::parse([
            "Protocol:s" => [
                "description" => "The protocol version.",
                "enum" => [\JsConnectPlugin::PROTOCOL_V2, \JsConnectPlugin::PROTOCOL_V3],
                "default" => \JsConnectPlugin::PROTOCOL_V3,
            ],
            "HashType:s" => ["description" => "Hash Algorithm", "enum" => hash_algos(), "default" => "md5"],
            "Trusted:b" => [
                "description" => "This is a trusted connection and can sync roles & permissions.",
                "default" => false,
            ],
            "TestMode:b" => [
                "description" => "This connection is in test-mode.",
                "default" => false,
            ],
        ]);
    }
}
