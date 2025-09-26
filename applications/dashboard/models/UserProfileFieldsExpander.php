<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\FeatureFlagHelper;

/**
 * Class for expanding the profile fields on a user record.
 */
class UserProfileFieldsExpander extends \Vanilla\Web\AbstractApiExpander
{
    /** @var string  */
    private $baseKey = "profileFields";

    /**
     * D.I.
     *
     * @param \Vanilla\Dashboard\Models\ProfileFieldModel $profileFieldModel
     */
    public function __construct(\Vanilla\Dashboard\Models\ProfileFieldModel $profileFieldModel)
    {
        $this->profileFieldModel = $profileFieldModel;
        $this->addExpandFields();
    }

    /**
     * Set the base key for the expander.
     *
     * @param string $baseKey
     */
    public function setBaseKey(string $baseKey): void
    {
        $this->baseKey = $baseKey;
    }

    /**
     * Add the expand fields using the base key.
     */
    private function addExpandFields(): void
    {
        $this->addExpandField("firstInsertUser.{$this->baseKey}", "firstInsertUserID")
            ->addExpandField("insertUser.{$this->baseKey}", "insertUserID")
            ->addExpandField("lastInsertUser.{$this->baseKey}", "lastInsertUserID")
            ->addExpandField("lastPost.insertUser.{$this->baseKey}", "lastPost.insertUserID")
            ->addExpandField("lastUser.{$this->baseKey}", "lastUserID")
            ->addExpandField("updateUser.{$this->baseKey}", "updateUserID")
            ->addExpandField("{$this->baseKey}", "userID");
    }

    /**
     * @inheritdoc
     */
    public function getFullKey(): string
    {
        return "users.{$this->baseKey}";
    }

    /**
     * @inheritdoc
     */
    public function resolveFragments(array $recordIDs): array
    {
        $fragmentsByUserIDs = $this->profileFieldModel->getUsersProfileFields($recordIDs);
        foreach ($fragmentsByUserIDs as $key => $fragmentByUserID) {
            if (empty($fragmentByUserID)) {
                $fragmentsByUserIDs[$key] = new stdClass();
            }
        }
        return $fragmentsByUserIDs;
    }

    /**
     * @inheritdoc
     */
    public function getPermission(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled(ProfileFieldModel::FEATURE_FLAG);
    }
}
