<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

use Vanilla\ArrayAccessTrait;

/**
 * Class SSOData
 *
 * This is a data object containing information returned by an SSOAuthenticator.
 * Adding methods to this object probably means that you are doing something wrong.
 *
 * @package Vanilla\Models
 */
class SSOData implements \ArrayAccess {
    use ArrayAccessTrait;

    /**
     * @var string Maps to "GDN_UserAuthenticationProvider.AuthenticationSchemeAlias"
     */
    public $authenticatorName;

    /**
     * @var string Maps to "GDN_UserAuthenticationProvider.ProviderKey"
     */
    public $authenticatorID;

    /**
     * @var bool Whether the authenticator can sync Roles or User info.
     */
    public $authenticatorIsTrusted;

    /**
     * @var string Maps to "GDN_UserAuthentication.ForeignUserKey"
     */
    public $uniqueID;

    /**
     * SSOData constructor.
     *
     * @param array $associativeArray
     * @throws \Exception If a non associative array is passed to the constructor.
     */
    public function __construct(array $associativeArray = []) {
        if (array_key_exists(0, $associativeArray)) {
            throw new \Exception(__CLASS__.' can only be initialized with an associative array.');
        }

        foreach($associativeArray as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Getter with default value.
     * Deprecate when we can use the coalesce operator.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed The extra information or the default if not found.
     */
    public function coalesce($name, $default = null) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return $default;
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
            if (!property_exists($this, $name)) {
                $invalidProperties[] = $name;
            }
        }

        if (count($invalidProperties)) {
            throw new \Exception("SSOData is invalid. The following properties are not set or empty: ".implode(',', $invalidProperties));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getArrayAccessSource() {
        return $this;
    }
}
