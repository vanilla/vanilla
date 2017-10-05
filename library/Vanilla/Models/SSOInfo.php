<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

use Vanilla\ArrayAccessTrait;

/**
 * Class SSOInfo
 *
 * This is a data object containing information returned by an SSOAuthenticator.
 * Adding methods to this object probably means that you are doing something wrong.
 *
 * @package Vanilla\Models
 */
class SSOInfo implements \ArrayAccess {
    use ArrayAccessTrait;

    /**
     * Maps to "GDN_UserAuthenticationProvider.AuthenticationSchemeAlias"
     *
     * @var string
     */
    public $authenticatorName;

    /**
     * Maps to "GDN_UserAuthenticationProvider.ProviderKey"
     *
     * @var string
     */
    public $authenticatorID;

    /**
     * Whether the authenticator can sync Roles or User info.
     *
     * @var bool
     */
    public $authenticatorIsTrusted;

    /**
     * Maps to "GDN_UserAuthentication.ForeignUserKey"
     *
     * @var string
     */
    public $uniqueID;

    /**
     * SSOInfo constructor.
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
     * @param $name
     * @param $default
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
     * @throw \Exception If the validation fails.
     */
    public function validate() {
        $required = ['authenticatorName', 'authenticatorID', 'authenticatorIsTrusted', 'uniqueID'];

        foreach ($required as $name) {
            // Empty is used on purpose here. Nothing should be of empty value in there.
            if (empty($this->$name)) {
                throw new \Exception("SSOInfo's '$name' is required.");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getArrayAccessSource() {
        return $this;
    }
}
