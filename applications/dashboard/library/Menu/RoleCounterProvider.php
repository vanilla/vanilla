<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for role model.
 */
class RoleCounterProvider implements CounterProviderInterface {

    /** @var \RoleModel */
    private $roleModel;

    /** @var \Gdn_Session */
    private $session;

    /**
     * Initialize class with dependencies
     *
     * @param \RoleModel $roleModel
     * @param \Gdn_Session $session
     */
    public function __construct(
        \RoleModel $roleModel,
        \Gdn_Session $session
    ) {
        $this->roleModel = $roleModel;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getMenuCounters(): array {
        $counters = [];
        $permissions = $this->session->getPermissions();
        if ($permissions->hasAny(['Garden.Users.Approve'])) {
            $counters[] = new Counter("Applicants", $this->roleModel->getApplicantCount());
        }
        return $counters;
    }
}
