<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Authenticator;

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
     * @throws \Exception
     * @param string $authenticatorID
     */
    public function __construct($authenticatorID) {
        $this->authenticatorID = $authenticatorID;

        $classParts = explode('\\', static::class);
        if (array_pop($classParts) !== $this->getNameImpl(static::class).'Authenticator') {
            throw new \Exception('Authenticator class name must end with Authenticator');
        }
    }

    /**
     * Validate an authentication by using the equest's data.
     *
     * @throws Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return array The user's information.
     */
    public abstract function validateAuthentication(RequestInterface $request);

    /**
     * Getter of the authenticator's ID.
     *
     * @return string
     */
    public final function getID() {
        return $this->authenticatorID;
    }

    /**
     * Default getName implementation.
     *
     * @return string
     */
    private function getNameImpl() {
        // return Name from "{Name}Authenticator"
        $classParts = explode('\\', static::class);
        return (string)substr(array_pop($classParts), 0, -strlen('Authenticator'));
    }

    /**
     * Getter of the authenticator's Name.
     *
     * @return string
     */
    public function getName() {
        return $this->getNameImpl();
    }
}
