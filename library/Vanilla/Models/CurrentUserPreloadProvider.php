<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Data;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Page preloader for current user.
 */
class CurrentUserPreloadProvider implements ReduxActionProviderInterface {

    /** @var \UsersApiController */
    private $usersApi;

    /** @var \SessionController */
    private $session;

    /**
     * DI.
     *
     * @param \UsersApiController $usersApi
     * @param \Gdn_Session $session
     */
    public function __construct(\UsersApiController $usersApi, \Gdn_Session $session) {
        $this->usersApi = $usersApi;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function createActions(): array {
        $user = $this->usersApi->get_me([]);
        $permissions = $this->usersApi->get_permissions($this->session->UserID);
        return [
            new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($user), []),
            new ReduxAction(\UsersApiController::PERMISSIONS_ACTION_CONSTANT, Data::box($permissions), [])
        ];
    }
}
