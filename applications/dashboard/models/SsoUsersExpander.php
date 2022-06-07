<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Permissions;
use Vanilla\Web\AbstractApiExpander;

/**
 * Expander for user's SSO IDs.
 */
class SsoUsersExpander extends AbstractApiExpander
{
    /** @var \UserModel */
    private $userModel;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * DI.
     *
     * @param \UserModel $userModel
     * @param ConfigurationInterface $config
     */
    public function __construct(\UserModel $userModel, ConfigurationInterface $config)
    {
        $this->userModel = $userModel;
        $this->config = $config;

        $this->addExpandField("firstInsertUser.ssoID", "firstInsertUserID")
            ->addExpandField("insertUser.ssoID", "insertUserID")
            ->addExpandField("lastInsertUser.ssoID", "lastInsertUserID")
            ->addExpandField("lastPost.insertUser.ssoID", "lastPost.insertUserID")
            ->addExpandField("lastUser.ssoID", "lastUserID")
            ->addExpandField("updateUser.ssoID", "updateUserID")
            ->addExpandField("user.ssoID", "userID")
            ->addExpandField("ssoID", "userID");
    }

    /**
     * @inheritdoc
     */
    public function getFullKey(): string
    {
        return "users.ssoID";
    }

    /**
     * @inheritdoc
     */
    public function resolveFragements(array $recordIDs): array
    {
        return $this->userModel->getDefaultSSOIDs($recordIDs);
    }

    /**
     * @inheritdoc
     */
    public function getPermission(): ?string
    {
        return $this->config->get("Garden.api.ssoIDPermission", Permissions::RANK_COMMUNITY_MANAGER);
    }
}
