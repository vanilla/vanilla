<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use UserModel;

/**
 * Expander for user roles.
 */
class RolesExpander extends \Vanilla\Web\AbstractApiExpander
{
    private UserModel $userModel;

    /**
     * DI.
     *
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
        $this->addExpandField("insertUserRoleIDs", "insertUserID");
    }

    /**
     * @inheritDoc
     */
    public function getFullKey(): string
    {
        return "roles";
    }

    /**
     * @inheritDoc
     */
    public function resolveFragements(array $recordIDs): array
    {
        return $this->userModel->getRoleIDsByUserIDs($recordIDs);
    }

    /**
     * @inheritDoc
     */
    public function getPermission(): ?string
    {
        return null;
    }
}
