<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Authenticator;

use Exception;
use Garden\Schema\Schema;
use Garden\Web\RequestInterface;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;

/**
 * Class SSOAuthenticator
 */
abstract class SSOAuthenticator extends Authenticator {

    /** @var bool */
    private $active;

    /**
     * Determine whether the authenticator can automatically link users by email.
     *
     * @var bool
     */
    private $autoLinkUser;

    /**
     * Whether or not, using the authenticator, the user can link his account from the profile page.'
     *
     * @var bool
     */
    private $linkSession;

    /**
     * Override 'name' in the schema for that particular instance.
     *
     * @var string
     */
    private $name;

    /**
     * Tells whether the data returned by this authenticator is authoritative or not.
     * User info/roles can only be synchronized by trusted authenticators.
     *
     * @var bool
     */
    private $signIn;

    /**
     * Whether or not the authenticator can be used to sign in.
     *
     * @var bool
     */
    private $trusted;

    /** @var string */
    private $signInUrl;

    /** @var string */
    private $signOutUrl;

    /** @var string */
    private $registerUrl;

    /** @var AuthenticatorModel */
    protected $authenticatorModel;

    /** @var bool Prevent this object from saving itself in the DB when using set{$property}() when constructing the object. */
    protected $saveState = false;

    /**
     * Authenticator constructor.
     *
     * @param string $authenticatorID Currently maps to "UserAuthenticationProvider.AuthenticationKey".
     * @param \Vanilla\Models\AuthenticatorModel $authenticatorModel
     *
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function __construct($authenticatorID, AuthenticatorModel $authenticatorModel) {
        $this->authenticatorModel = $authenticatorModel;
        $data = $this->loadData($authenticatorID);
        if (!$data) {
            throw new \Exception('Could not load authenticator data.');
        }

        $this->setAuthenticatorInfo($data);

        parent::__construct($authenticatorID);
        $this->saveState = true;
    }

    /**
     * Get this Authenticator schema.
     *
     * x-instance-required: This property is required when creating a new instance. See {@link getRequiredFields}.
     * x-instance-configurable: This property is configurable and can be provided when creating a new instance. See {@link getConfigurableFields}.
     *
     * @return Schema
     */
    public static function getAuthenticatorSchema(): Schema {
        $schema = parent::getAuthenticatorSchema()->merge(
            Schema::parse([
                'sso:o' => Schema::parse([
                    'canSignIn:b' => [
                        'description' => 'Whether or not the authenticator can be used to sign in.',
                        'default' => true,
                        'x-instance-configurable' => true,
                    ],
                    'canLinkSession:b'=> [
                        'description' => 'Whether or not, using the authenticator, the user can link his account from the profile page.',
                        'default' => false,
                        'x-instance-configurable' => true,
                    ],
                    'isTrusted:b' => [
                        'description' => 'Whether or not the authenticator is trusted to synchronize user information.',
                        'default' => false,
                        'x-instance-configurable' => true,
                    ],
                    'canAutoLinkUser:b' => [
                        'description' => 'Whether or not the authenticator can automatically link the incoming user information to an existing user account by using email address.',
                        'default' => false,
                        'x-instance-configurable' => true,
                    ],
                ])
            ])
        );

        $schema->setField('properties.authenticatorID.x-instance-required', true);
        $schema->setField('properties.authenticatorID.x-instance-configurable', true);

        $schema->setField('properties.type.x-instance-required', true);

        $schema->setField('properties.isActive.x-instance-required', true);
        $schema->setField('properties.isActive.x-instance-configurable', true);

        $schema->setField('properties.name.x-instance-required', true);
        $schema->setField('properties.name.x-instance-configurable', true);

        $schema->setField('properties.signInUrl.x-instance-required', true);
        $schema->setField('properties.signInUrl.x-instance-configurable', true);

        $schema->setField('properties.signOutUrl.x-instance-configurable', true);

        $schema->setField('properties.registerUrl.x-instance-configurable', true);

        // Make sure that the URLs are valid.
        $schema->setField('properties.signInUrl.format', 'uri');
        $schema->setField('properties.signOutUrl.format', 'uri');
        $schema->setField('properties.registerUrl.format', 'uri');

        return $schema;
    }

    /**
     * Returns a list of [flattened.Schema.Property => metaPropertyValue] which have a specific meta property.
     *
     * For example you could use this function to have a list of all properties and sub-properties that have a
     * default value set by doing: getSchemaPropertiesFilteredByExistingMeta($schema, 'default');
     *
     * See {@link getRequiredFields()} for an example.
     *
     * @param \Garden\Schema\Schema $schema
     * @param $metaProperty
     *
     * @return array
     */
    private static function getSchemaPropertiesFilteredByExistingMeta(Schema $schema, $metaProperty) {
        $properties = $schema->getField('properties');
        $result = [];

        foreach ($properties as $name => $data) {
            if ($data instanceof Schema) {
                $prefix = $name;
                $subResults = self::getSchemaPropertiesFilteredByExistingMeta($data, $metaProperty);
                foreach($subResults as $name => $value) {
                    $result["$prefix.$name"] = $value;
                }
            } else {
                if (array_key_exists($metaProperty, $data)) {
                    $result[$name] = $data[$metaProperty];
                }
            }
        }

        return $result;
    }

    /**
     * List of properties that MUST be passed to this type of authenticator when creating it.
     *
     * @return array A list of flattened keys/sub-keys of properties.
     */
    final public static function getRequiredFields() {
        return array_keys(self::getSchemaPropertiesFilteredByExistingMeta(static::getAuthenticatorSchema(), 'x-instance-required'));
    }

    /**
     * List of properties that can be passed to this type of authenticator when creating it.
     *
     * @return array list of flattened keys/sub-keys of properties.
     */
    final public static function getConfigurableFields() {
        return array_keys(self::getSchemaPropertiesFilteredByExistingMeta(static::getAuthenticatorSchema(), 'x-instance-configurable'));
    }

    /**
     * Return an array of all configurable fields that have a default value.
     *
     * @return array
     */
    final public static function getConfigurableFieldsDefaultValue() {
        $configurableFields = array_flip(self::getConfigurableFields());
        $fieldsWithDefaults = self::getSchemaPropertiesFilteredByExistingMeta(static::getAuthenticatorSchema(), 'default');

        return array_intersect_key($fieldsWithDefaults, $configurableFields);
    }

    /**
     * Load this authenticator instance's data.
     *
     * Highly coupled to AuthenticatorModel.
     *
     * Calling {@link AuthenticatorModel::createSSOAuthenticatorInstance($data)} put $data in memory and that data is
     * then passed back to this function from {@link AuthenticatorModel::getSSOAuthenticatorData()}.
     *
     * Calling {@link AuthenticatorModel::getSSOAuthenticatorData()} will return data from the database.
     *
     * @param string $authenticatorID
     * @return bool|mixed
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    protected function loadData(string $authenticatorID) {
        return $this->authenticatorModel->getSSOAuthenticatorData(self::getType(), $authenticatorID);
    }

    /**
     * Take an array of data matching {@link AuthenticatorModel::getAuthenticatorInfo()} and assign the values to this
     * authenticator's property. Extending classes might want to redefine this function to add extra functionalities to it.
     *
     * @param array $data
     *
     * @throws \Garden\Schema\ValidationException
     */
    protected function setAuthenticatorInfo(array $data) {
        // Let's set the defaults (based on the schema definition).
        foreach (self::getSchemaPropertiesFilteredByExistingMeta(static::getAuthenticatorSchema(), 'default') as $dotDelimitedField => $value) {
            if (!arrayPathExists(explode('.', $dotDelimitedField), $data)) {
                setvalr($dotDelimitedField, $data, $value);
            }
        }

        $this->setSignIn($data['sso']['canSignIn'] ?? false);
        $this->setLinkSession($data['sso']['canLinkSession'] ?? false);
        $this->setTrusted($data['sso']['isTrusted'] ?? false);
        $this->setAutoLinkUser($data['sso']['canAutoLinkUser'] ?? false);

        $currentlyAccessibleProperties = get_object_vars($this);

        // Set data to appropriate target.
        foreach($data as $key => $value) {
            // Set through method name can{Something}.
            if (substr($key, 0, 3) === 'can' && method_exists($this, $key)) {
                $this->{$key}($value);
            // Set through method name set{Something}. Note that the property has to be named is{Something}.
            } else if (preg_match('/^is([A-Z].+)/', $key, $matches) && method_exists($this, 'set'.$matches[1])) {
                $this->{'set'.$matches[1]}($value);
            // Set directly to variable.
            } else if (array_key_exists($key, $currentlyAccessibleProperties)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Mass update authenticator's data.
     *
     * @param array $data
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function updateAuthenticatorInfo(array $data) {
        $data = array_replace_recursive($this->getAuthenticatorInfo(), $data);
        $data = static::getAuthenticatorSchema()->validate($data);

        $this->saveState = false;
        $this->setAuthenticatorInfo($data);
        $this->saveState = true;

        $this->authenticatorModel->saveSSOAuthenticatorData($this);
    }

    /**
     * @inheritdoc
     */
    final protected function getAuthenticatorDefaultInfo(): array {
        return array_replace_recursive(
            parent::getAuthenticatorDefaultInfo(),
            [
                'name' => $this->getName(),
                'sso' => [
                    'canSignIn' => $this->canSignIn(),
                    'canLinkSession' => $this->canLinkSession(),
                    'isTrusted' => $this->isTrusted(),
                    'canAutoLinkUser' => $this->canAutoLinkUser(),
                ],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'buttonName' => sprintft('Sign In with %s', $this->name),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function setActive(bool $active) {
        $this->active = $active;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    public function isActive(): bool {
        return $this->active;
    }

    /**
     * Getter of autoLinkUser.
     *
     * @return bool
     */
    public function canAutoLinkUser(): bool {
        return $this->autoLinkUser;
    }

    /**
     * Setter of autoLinkUser.
     *
     * @param bool $autoLinkUser
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setAutoLinkUser(bool $autoLinkUser) {
        $this->autoLinkUser = $autoLinkUser;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    /**
     * Tell whether a user is linked or not to this authenticator.
     *
     * @param int $userID
     * @return bool
     */
    public function isUserLinked(int $userID): bool {
        return $this->authenticatorModel->isUserLinkedToSSOAuthenticator(self::getType(), $this->getID(), $userID);
    }

    /**
     * Getter of linkSession.
     *
     * @return bool
     */
    public function canLinkSession(): bool {
        return $this->linkSession;
    }

    /**
     * Setter of linkSession.
     *
     * @param bool $linkSession
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setLinkSession(bool $linkSession) {
        $this->linkSession = $linkSession;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    /**
     * Getter of name.
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Setter of $name
     *
     * @param string $name
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setName(string $name) {
        $this->name = $name;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    /**
     * Getter of signIn.
     *
     * @return bool
     */
    public function canSignIn(): bool {
        return $this->signIn;
    }

    /**
     * Setter of signIn.
     *
     * @param bool $signIn
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setSignIn(bool $signIn) {
        $this->signIn = $signIn;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    /**
     * Getter of trusted.
     */
    final public function isTrusted(): bool {
        return $this->trusted;
    }

    /**
     * Setter of trusted.
     *
     * @param bool $trusted
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setTrusted(bool $trusted) {
        $this->trusted = $trusted;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRegisterUrl() {
        return $this->registerUrl;
    }

    /**
     * @inheritdoc
     */
    public function getSignInUrl() {
        return $this->signInUrl;
    }

    /**
     * @inheritdoc
     */
    public function getSignOutUrl() {
        return $this->signOutUrl;
    }

    /**
     * Initiate the SSO Authentication process.
     *
     * This method can be redefined it there is anything that needs to be done before going to the provider's sign in page.
     * Adding a nonce to the URL for example.
     */
    public function initiateAuthentication() {
        redirectTo($this->getSignInUrl(), 302, false);
    }

    /**
     * Validate an authentication by using the request's data.
     *
     * @throws Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return SSOData The user's information.
     */
    final public function validateAuthenticationImpl(RequestInterface $request) {
        $ssoData = $this->sso($request);
        $ssoData->validate();

        return $ssoData;
    }

    /**
     * Core implementation of the validateAuthentication() function.
     *
     * @throws Exception Reason why the authentication failed.
     *
     * @param RequestInterface $request
     * @return SSOData The user's information.
     */
    protected abstract function sso(RequestInterface $request): SSOData;
}
