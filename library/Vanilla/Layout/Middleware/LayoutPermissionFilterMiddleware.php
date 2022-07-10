<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Vanilla\Layout\Middleware;

use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\Middleware\AbstractMiddleware;
use Garden\Schema\Schema;

/**
 * Middleware that filters based on roleID.
 */
class LayoutPermissionFilterMiddleware extends AbstractMiddleware {

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
     * Process middleware params
     *
     * @param array $nodeData
     * @param array $middlewareParams
     * @param array $hydrateParams
     * @param DataResolverInterface $next
     * @return mixed|null
     */
    protected function processInternal(array $nodeData, array $middlewareParams, array $hydrateParams, DataResolverInterface $next) {
        $userRoleIDs = $this->userModel->getRoleIDs($this->session->UserID);
        if (isset($middlewareParams['roleIDs'])) {
            $response = $this->filterRoleIDs($middlewareParams, $userRoleIDs, $nodeData);
            if (is_null($response)) {
                return null;
            }
        }
        return $next->resolve($nodeData, $hydrateParams);
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
        $objRoleIds = $roleFilter['roleIDs'];
        if (!array_intersect($objRoleIds, $roleIDs)) {
            return null;
        }
        return $data;
    }

    /**
     * Get the middleware schema.
     *
     * @return Schema
     */
    public function getSchema(): Schema {
        $schema = new Schema([
            "x-no-hydrate" => true,
            "description" => "Add role based fitlers for the current node. Only roles configured here will see the contents of the node.",
            "type" => "object",
                "properties" => [
                    "roleIDs" => [
                        "type" => "array",
                        "description" => "A list of roleIDs that should see the node.",
                        "items" => [
                            "type" => "integer",
                        ]
                    ]
                ]
        ]);
        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return "role-filter";
    }
}
