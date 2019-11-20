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

    /**
     * DI.
     *
     * @param \UsersApiController $usersApi
     */
    public function __construct(\UsersApiController $usersApi) {
        $this->usersApi = $usersApi;
    }


    /**
     * @inheritdoc
     */
    public function createActions(): array {
        $user = $this->usersApi->index([]);

        return [
            new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($user), [])
        ];
    }
}
