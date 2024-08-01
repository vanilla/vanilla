<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Data;
use Vanilla\Http\InternalClient;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Page preloader for current user.
 */
class CurrentUserPreloadProvider implements ReduxActionProviderInterface
{
    /** @var InternalClient */
    private $internalClient;

    /** @var \Gdn_Session */
    private $session;

    /**
     * DI.
     *
     * @param InternalClient $internalClient
     * @param \Gdn_Session $session
     */
    public function __construct(InternalClient $internalClient, \Gdn_Session $session)
    {
        $this->internalClient = $internalClient;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function createActions(): array
    {
        $user = $this->internalClient->get("/api/v2/users/me")->getBody();
        $permissions = $this->internalClient
            ->get("/api/v2/users/{$this->session->UserID}/permissions", ["expand" => "junctions"])
            ->getBody();
        return [
            new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($user), []),
            new ReduxAction(\UsersApiController::PERMISSIONS_ACTION_CONSTANT, Data::box($permissions), []),
        ];
    }
}
