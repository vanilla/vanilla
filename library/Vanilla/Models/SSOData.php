<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

/**
 * Class SSOData
 */
class SSOData implements \JsonSerializable {

    /** @var array $userFields */
    private static $userFields = ['name', 'email', 'photo', 'roles'];

    /** @var string Maps to "GDN_UserAuthenticationProvider.AuthenticationSchemeAlias" */
    private $authenticatorName;

    /** @var string Maps to "GDN_UserAuthenticationProvider.ProviderKey" */
    private $authenticatorID;

    /** @var bool Whether the authenticator can sync Roles or User info. */
    private $authenticatorIsTrusted;

    /** @var string Maps to "GDN_UserAuthentication.ForeignUserKey" */
    private $uniqueID;

    /** @var array $user */
    private $user = [];

    /** @var array $extra */
    private $extra = [];

    /**
     * SSOData constructor.
     *
     * @param string $authenticatorName
     * @param string $authenticatorID
     * @param bool $authenticatorIsTrusted
     * @param string $uniqueID
     * @param array $userData
     * @param array $extra
     * @throws \Exception If a non associative array is passed to the constructor.
     */
    public function __construct(
        $authenticatorName,
        $authenticatorID,
        $authenticatorIsTrusted,
        $uniqueID,
        $userData = [],
        $extra = []
    ) {
        $this->authenticatorName = $authenticatorName;
        $this->authenticatorID = $authenticatorID;
        $this->authenticatorIsTrusted = $authenticatorIsTrusted;
        $this->uniqueID = $uniqueID;
        $this->extra = $extra;

        foreach ($userData as $key => $value) {
            $this->setUserValue($key, $value);
        }
    }

    /**
     * @return string
     */
    public function getAuthenticatorName() {
        return $this->authenticatorName;
    }

    /**
     * @param $authenticatorName
     * @return $this
     */
    public function setAuthenticatorName($authenticatorName) {
        $this->authenticatorName = $authenticatorName;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthenticatorID() {
        return $this->authenticatorID;
    }

    /**
     * @param $authenticatorID
     * @return $this
     */
    public function setAuthenticatorID($authenticatorID) {
        $this->authenticatorID = $authenticatorID;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAuthenticatorIsTrusted() {
        return $this->authenticatorIsTrusted;
    }

    /**
     * @param $authenticatorIsTrusted
     * @return $this
     */
    public function setAuthenticatorIsTrusted($authenticatorIsTrusted) {
        $this->authenticatorIsTrusted = $authenticatorIsTrusted;
        return $this;
    }

    /**
     * @return string
     */
    public function getUniqueID() {
        return $this->uniqueID;
    }

    /**
     * @param $uniqueID
     * @return $this
     */
    public function setUniqueID($uniqueID) {
        $this->uniqueID = $uniqueID;
        return $this;
    }

    /**
     * @return array
     */
    public function getUser() {
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
     * @return array
     */
    public function getExtra() {
        return $this->extra;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getExtraValue($key) {
        return isset($this->extra[$key]) ? $this->extra[$key] : null;
    }

    /**
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
    public static function splitProviderData($data) {
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
        $required = ['authenticatorName', 'authenticatorID', 'authenticatorIsTrusted', 'uniqueID'];

        $invalidProperties = [];
        foreach ($required as $name) {
            if ($this->$name === null) {
                $invalidProperties[] = $name;
            }
        }

        if (count($invalidProperties)) {
            throw new \Exception("SSOData is invalid. The following properties are not set: ".implode(',', $invalidProperties));
        }
    }

    /**
     * Create an SSOData object from an array of data.
     *
     * @throws \ErrorException if the SSOData object is not valid.
     * @param array $array
     * @return SSOData
     */
    public static function fromArray(array $array) {
        $ssoData = new SSOData(
            array_key_exists('authenticatorName', $array) ? $array['authenticatorName'] : null,
            array_key_exists('authenticatorID', $array) ? $array['authenticatorID'] : null,
            array_key_exists('authenticatorIsTrusted', $array) ? $array['authenticatorIsTrusted'] : null,
            array_key_exists('uniqueID', $array) ? $array['uniqueID'] : null,
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
