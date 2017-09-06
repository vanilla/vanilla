<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla;

use Garden\Web\RequestInterface;

abstract class Authenticator {

    /**
     * Identifier of this authenticator instance.
     *
     * Extending classes will most likely require to have a dependency on RequestInterface so that they can
     * fetch the ID from the URL and throw an exception if it is not found or invalid.
     *
     * @var string
     */
    private $authenticatorID;

    /**
     * Authenticator constructor.
     *
     * @param string $authenticatorID
     */
    public function __construct($authenticatorID) {
        $this->authenticatorID = $authenticatorID;
    }

    /**
     * Authenticate an user by using the request's data.
     *
     * @throw Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return array The user's information.
     */
    public abstract function authenticate(RequestInterface $request);

    /**
     * Getter of the authenticator's ID.
     *
     * @return string
     */
    public final function getID() {
        return $this->authenticatorID;
    }
}
