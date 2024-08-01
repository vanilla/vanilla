<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Contracts\Models\SiteTotalProviderInterface;

/**
 * Get site totals for users.
 */
class UserSiteTotalProvider implements SiteTotalProviderInterface
{
    /** @var \UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param \UserModel $userModel
     */
    public function __construct(\UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Get total count of users.
     * Soft-deleted users are excluded from this count.
     *
     * {@inheritdoc}
     */
    public function calculateSiteTotalCount(): int
    {
        $result = $this->userModel->getCount(["Deleted" => 0]);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getSiteTotalRecordType(): string
    {
        return "user";
    }

    /**
     * @inheritdoc
     */
    public function getTableName(): string
    {
        return "User";
    }
}
