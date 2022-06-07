<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Web\AbstractApiExpander;

/**
 * Expander of user fragments.
 */
class UsersExpander extends AbstractApiExpander
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

        $this->addExpandField("firstInsertUser", "firstInsertUserID")
            ->addExpandField("insertUser", "insertUserID")
            ->addExpandField("lastInsertUser", "lastInsertUserID")
            ->addExpandField("lastPost.insertUser", "lastPost.insertUserID")
            ->addExpandField("lastUser", "lastUserID")
            ->addExpandField("updateUser", "updateUserID")
            ->addExpandField("user", "userID");
    }

    /**
     * @inheritdoc
     */
    public function getFullKey(): string
    {
        return "users";
    }

    /**
     * @inheritdoc
     */
    public function getDefaultRecord(): ?array
    {
        return $this->userModel->getGeneratedFragment(\UserModel::GENERATED_FRAGMENT_KEY_UNKNOWN)->jsonSerialize();
    }

    /**
     * @inheritdoc
     */
    public function resolveFragements(array $recordIDs): array
    {
        return $this->userModel->getUserFragments($recordIDs);
    }

    /**
     * @inheritdoc
     */
    public function getPermission(): ?string
    {
        return null;
    }
}
