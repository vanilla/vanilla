<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
namespace Vanilla\OAuth2;

use Garden\Schema\Schema;
use Vanilla\Models\AuthenticatorTypeInterface;

/**
 * Authentication Type provider for `OAuth2`.
 */
class OAuth2AuthenticationTypeProvider implements AuthenticatorTypeInterface
{
    /**
     * @inheritdoc
     */
    public function getAuthenticatorType(): array
    {
        return [
            "authenticatorType" => "OAuth2",
            "name" => "OAuth 2.0",
            "description" => "Setup a OAuth 2.0 authentication.",
            "schema" => [
                "associationKey:s?" => [
                    "description" =>
                        "OAuth Configuration: Name (The name of the connection. This is displayed on some pages).",
                ],
                "secret:s" => [
                    "minLength" => 1,
                    "description" => "OAuth Configuration: Secret (Secret provided by the authentication provider).",
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
            "associationKey:s" => [
                "description" => "OAuth Configuration: Client ID (Unique ID of the authentication application).",
            ],
            "authorizeUrl:s" => [
                "description" =>
                    "OAuth Configuration: Authorize URL (URL where users sign-in with the authentication provider).",
            ],
            "tokenUrl:s" => [
                "description" => "OAuth Configuration: Token URL (Endpoint to retrieve the access token for a user).",
            ],
            "acceptedScope:s?" => [
                "description" => "Advanced Settings: Request Scope (Enter the scope to be sent with token requests).",
            ],
            "profileKeyEmail:s?" => [
                "description" => "General SSO Settings: Email (The key in the JSON array to designate emails).",
                "default" => "email",
            ],
            "profileKeyPhoto:s?" => [
                "description" => "General SSO Settings: Photo (The key in the JSON array to designate photo URL).",
                "default" => "picture",
            ],
            "profileKeyName:s?" => [
                "description" =>
                    "General SSO Settings: Display Name (The key in the JSON array to designate display name).",
                "default" => "displayname",
            ],
            "profileKeyFullName:s?" => [
                "description" => "General SSO Settings: Full Name (The key in the JSON array to designate full name).",
                "default" => "name",
            ],
            "profileKeyUniqueID:s?" => [
                "description" => "General SSO Settings: User ID (The key in the JSON array to designate user ID).",
                "default" => "user_id",
            ],
            "profileKeyRoles:s?" => [
                "description" => "General SSO Settings: Roles (The key in the JSON array to designate roles).",
                "default" => "roles",
            ],
            "prompt:s?" => [
                "description" => "Advanced Settings: Prompt (Prompt parameter set with authorize requests).",
                "enum" => ["consent", "consent and login", "login", "none"],
                "default" => "login",
            ],
            "bearerToken:b?" => [
                "description" =>
                    "Advanced Settings: Authorization Code in Header (When requesting the profile, pass the access token in the HTTP header. i.e Authorization: Bearer [accesstoken])",
                "default" => false,
            ],
            "allowAccessTokens:b?" => [
                "description" =>
                    "Advanced Settings: Allow Access Tokens (Allow this connection to issue API access tokens).",
                "default" => false,
            ],
            "baseUrl:s" => [
                "description" => "Should essentially be the protocol & domain of the `Authorize URL`.",
            ],
            "basicAuthToken:b?" => [
                "description" =>
                    "Advanced Settings: Basic Authorization Code in Header (When requesting the Access Token, pass the basic Auth token in the HTTP header. i.e Authorization: [Authorization =\> Basic base64_encode(\$rawToken)])",
                "default" => false,
            ],
            "isOidc:b?" => [
                "description" =>
                    "OAuth Configuration: This is an OIDC Connection (This connection should use OIDC ID Token instead of a profile URL).",
                "default" => false,
            ],
        ]);
    }
}
