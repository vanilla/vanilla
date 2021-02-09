<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\JSON\Transformer;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Attributes;
use Vanilla\Web\ApiFilterMiddleware;

/**
 * Class OAuth2Plugin
 *
 * Expose the functionality of the core class Gdn_OAuth2 to create SSO workflows.
 */

class OAuth2Plugin extends Gdn_OAuth2 {
    /**
     * @var string
     */
    private $currentClientID = '';

    /**
     * @var Gdn_AuthenticationProviderModel
     */
    private $authenticationProviderModel;

    /**
     * Set the key for saving OAuth settings in GDN_UserAuthenticationProvider.
     * @codeCoverageIgnore
     */
    public function __construct() {
        $this->setProviderKey('oauth2');
        $this->settingsView = 'plugins/settings/oauth2';
        $this->setAuthenticationSchemeAlias('oauth2');
        $this->clientIDField = Gdn_AuthenticationProviderModel::COLUMN_KEY;
    }

    /**
     * Create the structure in the database.
     */
    public function structure() {
        $providers = Gdn_AuthenticationProviderModel::getWhereStatic(['AuthenticationSchemeAlias' => $this->getProviderKey()]);
        if (empty($providers)) {
            // TODO: Take this out when the multi-connection OAuth UI is done.
            $model = $this->getAuthenticationProviderModel();
            $provider = [
                Gdn_AuthenticationProviderModel::COLUMN_KEY => $this->providerKey, // temp
                Gdn_AuthenticationProviderModel::COLUMN_ALIAS => $this->providerKey,
                'Name' => $this->providerKey,
                'AcceptedScope' => 'openid email profile',
                'ProfileKeyEmail' => 'email', // Can be overwritten in settings, the key the authenticator uses for email in response.
                'ProfileKeyPhoto' => 'picture',
                'ProfileKeyName' => 'nickname',
                'ProfileKeyFullName' => 'name',
                'ProfileKeyUniqueID' => 'sub',
                'ProfileKeyRoles' => 'roles'
            ];

            $model->save($provider);
        } else {
            // Fix the providers by migrating their authentication keys to the proper column.
            foreach ($providers as $provider) {
                if (!empty($provider[static::COLUMN_ASSOCIATION_KEY]) &&
                    $provider[Gdn_AuthenticationProviderModel::COLUMN_KEY] === $this->getProviderKey()
                ) {
                    $provider[Gdn_AuthenticationProviderModel::COLUMN_KEY] = $provider[static::COLUMN_ASSOCIATION_KEY];
                    unset($provider[static::COLUMN_ASSOCIATION_KEY]);
                    $this->getAuthenticationProviderModel()->save($provider);

                    \Gdn::sql()->put(
                        'UserAuthentication',
                        ['ProviderKey' => $provider[Gdn_AuthenticationProviderModel::COLUMN_KEY]],
                        ['ProviderKey' => $this->getProviderKey()]
                    );
                }
            }
        }
    }

    /**
     * Check if there is enough data to connect to an authentication provider.
     *
     * @return bool True if there is a secret and a client_id, false if not.
     */
    public function isConfigured() {
        $provider = $this->provider();
        return $this->isProviderConfigured($provider);
    }

    /**
     * Check whether or not a specific provider is configured.
     *
     * @param array $provider
     * @return bool
     */
    protected function isProviderConfigured(array $provider): bool {
        return is_array($provider) && !empty($provider['AssociationSecret']);
    }

    /**
     * Get a schema for all writeable fields in API requests.
     *
     * @return Schema
     */
    private function apiWriteSchema(): Schema {
        return Schema::parse([
            "name:s",
            "clientID:s",
            "secret:s",
            "urls:o" => [
                "authorizeUrl:s",
                "profileUrl:s",
                "registerUrl:s?",
                "signOutUrl:s?",
                "tokenUrl:s",
            ],
            "authenticationRequest:o?" => [
                "scope:s?",
                "prompt?" => [
                    "enum" => ["consent", "consent and login", "login", "none"],
                    "type" => "string",
                ],
            ],
            "useBearerToken:b?",
            "allowAccessTokens:b?",
            "userMappings:o?" => [
                "uniqueID:s?",
                "email:s?",
                "name:s?",
                "photoUrl:s?",
                "fullName:s?",
                "roles:s?",
            ],
        ]);
    }

    /**
     * Get extended OAuth2 configuration details for an authenticator.
     *
     * @param AuthenticatorsApiController $controller
     * @param int $id
     * @return Data
     */
    public function authenticatorsApiController_get_oauth2(AuthenticatorsApiController $controller, int $id): Data {
        $controller->permission("Garden.Settings.Manage");

        $in = $controller->schema([], "in");
        $out = $controller->schema($this->providerFragmentSchema(), "out");

        $row = $this->getAuthenticationProviderModel()->getID($id, DATASET_TYPE_ARRAY);
        if (empty($row)) {
            throw new NotFoundException("Authenticator");
        }

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        $response = new Data($result);
        $response->setMeta(ApiFilterMiddleware::FIELD_ALLOW, ["email"]);
        return $response;
    }

    /**
     * Update an OAuth2 provider via the API.
     *
     * @param AuthenticatorsApiController $controller
     * @param int $id
     * @param array $body
     * @return Data
     */
    public function authenticatorsApiController_patch_oauth2(AuthenticatorsApiController $controller, int $id, array $body = []): Data {
        $controller->permission("Garden.Settings.Manage");

        $in = $controller->schema($this->apiWriteSchema(), "in");
        $out = $controller->schema($this->providerFragmentSchema(), "out");

        $body = $in->validate($body, true);

        $model = $this->getAuthenticationProviderModel();

        $row = $this->getAuthenticationProviderModel()->getID($id, DATASET_TYPE_ARRAY);
        if (empty($row)) {
            throw new NotFoundException("Authenticator");
        }

        $fields = array_merge($row, $this->normalizeInput($body));
        $fields[$model->PrimaryKey] = $id;
        $model->save($fields);

        $row = $model->getID($id, DATASET_TYPE_ARRAY);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        $response = new Data($result);
        $response->setMeta(ApiFilterMiddleware::FIELD_ALLOW, ["email"]);
        return $response;
    }

    /**
     * Create an OAuth2 provider via the API.
     *
     * @param AuthenticatorsApiController $controller
     * @param array $body
     * @return Data
     */
    public function authenticatorsApiController_post_oauth2(AuthenticatorsApiController $controller, array $body = []): Data {
        $controller->permission("Garden.Settings.Manage");

        $in = $controller->schema($this->apiWriteSchema(), "in");
        $out = $controller->schema($this->providerFragmentSchema(), "out");

        $body = $in->validate($body);

        $model = $this->getAuthenticationProviderModel();

        if ($model->getProviderByKey($body["clientID"])) {
            throw new ClientException("An authenticator with this clientID already exists.");
        }

        $fields = $this->normalizeInput($body);
        $fields["AuthenticationSchemeAlias"] = "oauth2";
        $id = $model->save($fields, [Gdn_AuthenticationProviderModel::OPT_RETURN_KEY => false]);

        $row = $model->getID($id, DATASET_TYPE_ARRAY);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        $response = new Data($result, ["status" => 201]);
        $response->setMeta(ApiFilterMiddleware::FIELD_ALLOW, ["email"]);
        return $response;
    }

    /**
     * Massage a modern input format (i.e. API v2) into something compatible with legacy models.
     *
     * @param array $input
     * @return array
     */
    private function normalizeInput(array $input): array {
        $transformer = new Transformer([
            "AcceptedScope" => "/authenticationRequest/scope",
            "AllowAccessTokens" => "allowAccessTokens",
            "AuthenticationKey" => "clientID",
            "AuthorizeUrl" => "/urls/authorizeUrl",
            "Name" => "name",
            "RegisterUrl" => "/urls/registerUrl",
            "SignOutUrl" => "/urls/signOutUrl",
            "ProfileKeyEmail" => "/userMappings/email",
            "ProfileKeyFullName" => "/userMappings/fullName",
            "ProfileKeyName" => "/userMappings/name",
            "ProfileKeyPhoto" => "/userMappings/photoUrl",
            "ProfileKeyRoles" => "/userMappings/roles",
            "ProfileKeyUniqueID" => "/userMappings/uniqueID",
            "ProfileUrl" => "/urls/profileUrl",
            "Prompt" => "/authenticationRequest/prompt",
            "Secret" => "secret",
            "TokenUrl" => "/urls/tokenUrl",
            "BearerToken" => "useBearerToken",
        ]);
        $result = $transformer->transform($input);
        return $result;
    }

    /**
     * Massage legacy database row data into something better suited for modern responses (i.e. API v2).
     *
     * @param array $output
     * @return array
     */
    private function normalizeOutput(array $output): array {
        $result = $this->getAuthenticationProviderModel()->normalizeRow($output, [
            "authenticationRequest" => [
                "prompt" => "Prompt",
                "scope" => "AcceptedScope",
            ],
            "allowAccessTokens" => "AllowAccessTokens",
            "urls" => [
                "authorizeUrl" => "AuthorizeUrl",
                "tokenUrl" => "TokenUrl",
            ],
            "userMappings" => [
                "ProfileKeyEmail" => "/userMappings/email",
                "ProfileKeyFullName" => "/userMappings/fullName",
                "ProfileKeyName" => "/userMappings/name",
                "ProfileKeyPhoto" => "/userMappings/photoUrl",
                "ProfileKeyRoles" => "/userMappings/roles",
                "ProfileKeyUniqueID" => "/userMappings/uniqueID",
            ],
            "secret" => "Secret",
            "useBearerToken" => "BearerToken",
        ]);

        $result["authenticationRequest"] = new Attributes($result["authenticationRequest"] ?? []);
        $result["userMappings"] = new Attributes($result["userMappings"] ?? []);
        return $result;
    }

    /**
     * Get the instance of the authentication provider model used for various operations.
     *
     * @return Gdn_AuthenticationProviderModel
     */
    private function getAuthenticationProviderModel(): Gdn_AuthenticationProviderModel {
        if ($this->authenticationProviderModel === null) {
            $this->authenticationProviderModel = new Gdn_AuthenticationProviderModel();
        }
        return $this->authenticationProviderModel;
    }

    /**
     *  Return all the information saved in provider table.
     *
     * @return array Stored provider data (secret, client_id, etc.).
     */
    public function provider() {
        if (!$this->provider) {
            if ($this->getCurrentClientID()) {
                $this->provider = Gdn_AuthenticationProviderModel::getProviderByKey($this->getCurrentClientID());
            } else {
                // There is no client ID. Make sure there is only one OAuth provider in this case.
                $providers = Gdn_AuthenticationProviderModel::getWhereStatic(
                    [\Gdn_AuthenticationProviderModel::COLUMN_ALIAS => $this->getProviderKey()],
                    '',
                    'asc',
                    2
                );

                if (empty($providers)) {
                    throw new Gdn_UserException("There are no configured OAuth authenticators");
                } elseif (count($providers) > 1) {
                    throw new Gdn_UserException("There are multiple OAuth authenticators, but you didn't specify a client ID.");
                }

                $this->provider = reset($providers);
            }
        }

        return $this->provider;
    }

    /**
     * The currently working OAuth client ID if there are multiple connections.
     *
     * @return string
     */
    public function getCurrentClientID(): string {
        return $this->currentClientID;
    }

    /**
     * Set the current working client ID if there are multiple connections.
     *
     * @param string $currentClientID
     * @return string Returns the previous client ID.
     */
    public function setCurrentClientID(string $currentClientID): string {
        $bak = $this->currentClientID;
        $this->currentClientID = $currentClientID;
        $this->provider = [];

        return $bak;
    }

    /**
     * Set the current provider.
     *
     * This is an optimization method so that the current provider can be set if known rather then just setting the client ID.
     *
     * @param array $provider
     * @return array
     */
    public function setProvider(array $provider): array {
        $bak = $this->provider;
        $this->provider = $provider;
        $this->currentClientID = $provider[\Gdn_AuthenticationProviderModel::COLUMN_KEY];
        return (array)$bak;
    }

    /**
     * Set the current client ID based on an optional client passed in the query string.
     *
     * @param \Garden\Web\RequestInterface $request
     */
    public function setCurrentClientIDFromRequest(\Garden\Web\RequestInterface $request): void {
        $query = $request->getQuery();
        $clientID = $query['client_id'] ?? ($query['clientID'] ?? '');
        $this->setCurrentClientID($clientID);
    }

    /**
     * {@inheritdoc}
     */
    public function entryRedirectEndpoint(EntryController $sender, $state = '') {
        $this->setCurrentClientIDFromRequest($sender->Request);
        parent::entryRedirectEndpoint($sender, $state);
    }

    /**
     * {@inheritDoc}
     */
    public function entryEndpoint($sender, $code, $state = '') {
        $stateArray = $this->decodeState($state);
        if (!array_key_exists('cid', $stateArray)) {
            throw new \Gdn_UserException("The client ID was missing from the state.", 400);
        }
        $this->setCurrentClientID($stateArray['cid']);
        parent::entryEndpoint($sender, $code, $state);
    }

    /**
     * {@inheritdoc}
     */
    public function entryController_signIn_handler($sender, $args) {
        if (!isset($sender->Data['Methods'])) {
            return;
        }

        $providers = \Gdn_AuthenticationProviderModel::getWhereStatic([
            \Gdn_AuthenticationProviderModel::COLUMN_ALIAS => $this->getProviderKey(),
            'Active' => true,
            'Visible' => true,
            'IsDefault' => false,
        ]);

        foreach ($providers as $provider) {
            if (empty($provider['AuthorizeUrl'])) {
                continue;
            }
            $this->setProvider($provider);
            $method = [
                'Name' => $this->getProviderKey(),
                'SignInHtml' => $this->signInButtonFromProvider($provider),
            ];
            $sender->Data['Methods'][] = $method;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function issueAccessToken(string $clientID, string $oauthAccessToken): array {
        $this->setCurrentClientID($clientID);
        return parent::issueAccessToken($clientID, $oauthAccessToken);
    }

    /**
     * Try and set the provider if the supplied default provider matches this one.
     *
     * @param array $defaultProvider The current default provider.
     * @return bool Returns **true** if the default provider was OAuth and was set or **false** otherwise.
     */
    private function trySetDefaultProvider($defaultProvider): bool {
        if (!is_array($defaultProvider)) {
            return false;
        }
        if ($defaultProvider[\Gdn_AuthenticationProviderModel::COLUMN_ALIAS] !== $this->getProviderKey() ||
            !$this->isProviderConfigured($defaultProvider)
        ) {
            return false;
        }
        $this->setProvider($defaultProvider);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function entryController_overrideSignIn_handler($sender, $args) {
        if (!$this->trySetDefaultProvider($args['DefaultProvider'])) {
            return;
        }
        parent::entryController_overrideSignIn_handler($sender, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function entryController_overrideRegister_handler($sender, $args) {
        if (!$this->trySetDefaultProvider($args['DefaultProvider'])) {
            return;
        }
        return parent::entryController_overrideRegister_handler($sender, $args);
    }

    /**
     * Get the current client ID.
     *
     * @param array $state
     * @return string
     */
    public function authorizeUri($state = []) {
        $r = parent::authorizeUri($state);
        if ($id = $this->getCurrentClientID()) {
            $r = \Vanilla\Utility\UrlUtils::concatQuery($r, 'client_id='.urlencode($this->getCurrentClientID()));
        }
        return $r;
    }
}
