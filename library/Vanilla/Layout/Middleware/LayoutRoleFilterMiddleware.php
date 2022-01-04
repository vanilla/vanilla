<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Vanilla\Layout\Middleware;

use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\MiddlewareInterface;
use Garden\Web\Data;

/**
 * Middleware that filters based on roleID.
 */
class LayoutRoleFilterMiddleware implements MiddlewareInterface {

    /** @var \Gdn_Session $session */
    private $session;

    /** @var \UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param \Gdn_Session $session
     * @param \UserModel $userModel
     */
    public function __construct(\Gdn_Session $session, \UserModel $userModel) {
        $this->session = $session;
        $this->userModel = $userModel;
    }


    /**
     * {@inheritDoc}
     */
    public function process(array $data, array $params, DataResolverInterface $next) {
        if (!empty($data['$middleware'])) {
            $userRoleIDs = $this->userModel->getRoleIDs($this->session->UserID);
            $response = $this->filterRoleIDs($data['$middleware'], $userRoleIDs, $data);
            if (is_null($response)) {
                return null;
            }
        }
        return $next->resolve($data, $params);
    }

    /**
     * Filter a node if the user doesn't have the right roleIDs.
     *
     * @param array $roleFilter The middleware definition.
     * @param int[] $roleIDs The expectd roleID.s
     * @param array $data The node data.
     *
     * @return int[]|null
     */
    private function filterRoleIDs(array $roleFilter, array $roleIDs, array $data): ?array {
        $objRoleIds = $roleFilter['role-filter']['roleIDs'];
        if (!array_intersect($objRoleIds, $roleIDs)) {
            return null;
        }
        return $data;
    }
}
