<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

/**
 * Class SSOData
 */
class SSOData implements \JsonSerializable {

    /** @var array $userFields */
    private static $userFields = ['name', 'email', 'photoUrl', 'roles'];

    /** @var string Maps to "GDN_UserAuthenticationProvider.AuthenticationSchemeAlias" */
    private $authenticatorType;

    /** @var string Maps to "GDN_UserAuthenticationProvider.ProviderKey" */
    private $authenticatorID;

    /** @var string Maps to "GDN_UserAuthentication.ForeignUserKey" */
    private $uniqueID;

    /** @var array $user */
    private $user = [];

    /** @var array $extra */
    private $extra = [];

    /**
     * SSOData constructor.
     *
     * @param string $authenticatorType
     * @param string $authenticatorID
     * @param string $uniqueID
     * @param array $userData
     * @param array $extra
     * @throws \Exception If a non associative array is passed to the constructor.
     */
    public function __construct(
        string $authenticatorType,
        string $authenticatorID,
        string $uniqueID,
        array $userData = [],
        array $extra = []
    ) {
        $this->authenticatorType = $authenticatorType;
        $this->authenticatorID = $authenticatorID;
        $this->uniqueID = $uniqueID;
        $this->extra = $extra;

        foreach ($userData as $key => $value) {
            $this->setUserValue($key, $value);
        }
    }

    /**
     * Getter of authenticatorType.
     *
     * @return string
     */
    public function getAuthenticatorType(): string {
        return $this->authenticatorType;
    }

    /**
     * Setter of authenticatorType.
     *
     * @param string $authenticatorType
     * @return $this
     */
    public function setAuthenticatorType(string $authenticatorType) {
        $this->authenticatorType = $authenticatorType;

        return $this;
    }

    /**
     * Getter of authenticatorID.
     *
     * @return string
     */
    public function getAuthenticatorID(): string {
        return $this->authenticatorID;
    }

    /**
     * Setter of authenticatorID.
     *
     * @param string $authenticatorID
     */
    public function setAuthenticatorID(string $authenticatorID) {
        $this->authenticatorID = $authenticatorID;
    }

    /**
     * Getter of uniqueID.
     *
     * @return string
     */
    public function getUniqueID(): string {
        return $this->uniqueID;
    }

    /**
     * Setter of uniqueID.
     *
     * @param string $uniqueID
     * @return $this
     */
    public function setUniqueID(string $uniqueID) {
        $this->uniqueID = $uniqueID;

        return $this;
    }

    /**
     * @return array
     */
    public function getUser(): array {
        return $this->user;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getUserValue($key) {
        return isset($this->user[$key]) ? $this->user[$key] : null;
    }

    /**
     * Setter of user's value.
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function setUserValue($key, $value) {
        if (in_array($key, self::$userFields)) {
            $this->user[$key] = $value;
        }
        return $this;
    }

    /**
     * Getter of extra.
     *
     * @return array
     */
    public function getExtra() {
        return $this->extra;
    }

    /**
     * Getter of extra's value.
     *
     * @param $key
     * @return mixed|null
     */
    public function getExtraValue($key) {
        return isset($this->extra[$key]) ? $this->extra[$key] : null;
    }

    /**
     * Setter of extra's value.
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function setExtraValue($key, $value) {
        $this->extra[$key] = $value;
        return $this;
    }

    /**
     * Split user data from extra data.
     *
     * @param $data
     * @return array [$userData, $extraData]
     */
    public static function splitProviderData($data): array {
        return [
            array_intersect_key($data, array_flip(self::$userFields)),
            array_diff_key($data, array_flip(self::$userFields)),
        ];
    }

    /**
     * Validate this object.
     *
     * @throws \Exception If the validation fails.
     */
    public function validate() {
        $required = ['authenticatorType', 'authenticatorID', 'uniqueID'];

        $invalidProperties = [];
        foreach ($required as $name) {
            if ($this->$name === '') {
                $invalidProperties[] = $name;
            }
        }

        if (count($invalidProperties)) {
            throw new \Exception('SSOData is invalid. The following properties are not set: '.implode(',', $invalidProperties));
        }
    }

    /**
     * Create an SSOData object from an array of data.
     *
     * @param array $array
     *
     * @return SSOData
     * @throws \Exception
     */
    public static function fromArray(array $array): SSOData {
        $ssoData = new SSOData(
            array_key_exists('authenticatorType', $array) ? $array['authenticatorType'] : '',
            array_key_exists('authenticatorID', $array) ? $array['authenticatorID'] : '',
            array_key_exists('uniqueID', $array) ? $array['uniqueID'] : '',
            array_key_exists('user', $array) ? $array['user'] : [],
            array_key_exists('extra', $array) ? $array['extra'] : []
        );
        $ssoData->validate();
        return $ssoData;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() {
        return get_object_vars($this);
    }
}
