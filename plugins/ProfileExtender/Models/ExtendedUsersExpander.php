<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Models;

use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\FeatureFlagHelper;
use Vanilla\Web\AbstractApiExpander;

/**
 * Expander for profile extender fields.
 */
class ExtendedUsersExpander extends AbstractApiExpander
{
    /** @var \ProfileExtenderPlugin */
    private $profileExtenderPlugin;

    /** @var ProfileFieldModel */
    private $profileFieldModel;

    /**
     * DI.
     *
     * @param \ProfileExtenderPlugin $profileExtenderPlugin
     */
    public function __construct(\ProfileExtenderPlugin $profileExtenderPlugin, ProfileFieldModel $profileFieldModel)
    {
        $this->profileExtenderPlugin = $profileExtenderPlugin;
        $this->profileFieldModel = $profileFieldModel;

        $this->addExpandField("firstInsertUser.extended", "firstInsertUserID")
            ->addExpandField("insertUser.extended", "insertUserID")
            ->addExpandField("lastInsertUser.extended", "lastInsertUserID")
            ->addExpandField("lastPost.insertUser.extended", "lastPost.insertUserID")
            ->addExpandField("lastUser.extended", "lastUserID")
            ->addExpandField("updateUser.extended", "updateUserID")
            ->addExpandField("extended", "userID");
    }

    /**
     * @inheritdoc
     */
    public function getFullKey(): string
    {
        return "users.extended";
    }

    /**
     * @inheritdoc
     */
    public function resolveFragements(array $recordIDs): array
    {
        if (FeatureFlagHelper::featureEnabled(ProfileFieldModel::FEATURE_FLAG)) {
            return $this->profileFieldModel->getUsersProfileFields($recordIDs);
        }
        return $this->profileExtenderPlugin->getUserProfileValuesChecked($recordIDs);
    }

    /**
     * @inheritdoc
     */
    public function getPermission(): ?string
    {
        return null;
    }
}
