<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Auth;

use Vanilla\SessionInterface;

/**
 * An authenticator that can authenticate against several child authenticators.
 */
class MultiAuthenticator implements AuthenticatorInterface {
    /**
     * @var array
     */
    private $authenticators;

    /**
     * Add an authenticator to the list of authenticators.
     *
     * @param AuthenticatorInterface $authenticator The authenticator to add.
     * @param bool $persist Whether or not the authenticator should be used for persistence.
     * @param bool $prepend If true the authenticator will be prepended to the beginning of the list.
     * @return $this
     */
    public function addAuthenticator(AuthenticatorInterface $authenticator, $persist = false, $prepend = false) {
        if ($prepend) {
            $this->authenticators = array_merge([[$authenticator, $persist]], $this->authenticators);
        } else {
            $this->authenticators[] = [$authenticator, $persist];
        }
        return $this;
    }

    /**
     * Remove an authenticator from the list of authenticators.
     *
     * @param AuthenticatorInterface $authenticator The authenticator to remove.
     * @return $this
     */
    public function removeAuthenticator(AuthenticatorInterface $authenticator) {
        foreach ($this->authenticators as $i => list($item, $persist)) {
            /* @var AuthenticatorInterface $authenticator */
            if ($authenticator === $item) {
                unset($this->authenticators[$i]);
                break;
            }
        }
        return $this;
    }

    /**
     * Authenticate a session.
     *
     * @param SessionInterface $session The session to authenticate.
     */
    public function authenticate(SessionInterface $session) {
        foreach ($this->authenticators as list($authenticator, $persist)) {
            /* @var AuthenticatorInterface $authenticator */
            if ($session->getUserID()) {
                break;
            }

            $authenticator->authenticate($session);
        }
    }

    /**
     * Persist a session to the cookie.
     *
     * @param SessionInterface $session The session to persist.
     * @param bool $remember Whether the user is supposed to be remembered.
     */
    public function persist(SessionInterface $session, $remember = false) {
        foreach ($this->authenticators as list($authenticator, $persist)) {
            /* @var AuthenticatorInterface $authenticator */
            if ($persist) {
                $authenticator->persist($session, $remember);
            }
        }
    }

    /**
     * Destroy a persisted session.
     */
    public function destroy() {
        foreach ($this->authenticators as list($authenticator, $persist)) {
            /* @var AuthenticatorInterface $authenticator */
            if ($persist) {
                $authenticator->destroy();
            }
        }
    }
}
