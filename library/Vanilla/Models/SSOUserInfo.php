<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

use Vanilla\ArrayAccessTrait;

/**
 * Class SSOUserInfo
 *
 * This is a data object containing user's information returned by an SSOAuthenticator.
 * Adding methods to this object probably means that you are doing something wrong.
 *
 * @package Vanilla\Models
 */
class SSOUserInfo implements \ArrayAccess {
    use ArrayAccessTrait;

    /**
     * Maps to "GDN_UserAuthentication.ProviderKey"
     *
     * @var string
     */
    public $authenticatorID;

    /**
     * The user's email.
     * May or no be set depending on "Garden.Registration.NoEmail".
     *
     * @var string
     */
    public $email;

    /**
     * The user's name.
     *
     * @var string
     */
    public $name;

    /**
     * Maps to "GDN_UserAuthentication.ForeignUserKey"
     *
     * @var string
     */
    public $uniqueID;

    /**
     * Validate this object.
     *
     * @throw Exception If the validation fails.
     */
    public function validate() {
        $required = ['authenticatorID', 'name', 'uniqueID'];
        if (!c('Garden.Registration.NoEmail', false)) {
            $required[] = 'email';
        }

        foreach ($required as $name) {
            if (empty($this->$name)) {
                throw new Exception("SSOUserInfo's '$name' is required.");
            }
        }
    }
}
