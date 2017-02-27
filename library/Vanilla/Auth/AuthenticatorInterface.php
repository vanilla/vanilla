<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Auth;


use Vanilla\SessionInterface;

interface AuthenticatorInterface {
    /**
     * Authenticate a session.
     *
     * @param SessionInterface $session The session to authenticate.
     */
    public function authenticate(SessionInterface $session);

    /**
     * Persist a session to the cookie.
     *
     * @param SessionInterface $session The session to persist.
     * @param bool $remember Whether the user is supposed to be remembered.
     */
    public function persist(SessionInterface $session, $remember = false);

    /**
     * Destroy a persisted session.
     */
    public function destroy();
}
