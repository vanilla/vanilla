<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Web\AbstractApiExpander;

/**
 * Expander class for user signatures.
 */
class UserSignatureExpander extends AbstractApiExpander
{
    public SignaturesPlugin $signaturesPlugin;

    /**
     * DiscussionWarningExpander constructor.
     *
     * @param SignaturesPlugin $signaturesPlugin
     */
    public function __construct(SignaturesPlugin $signaturesPlugin)
    {
        $this->addExpandField("insertUser.signature", "insertUserID");
        $this->signaturesPlugin = $signaturesPlugin;
    }

    /**
     * @inheritdoc
     */
    public function getFullKey(): string
    {
        return "users.signature";
    }

    /**
     * @inheritDoc
     */
    public function getPermission(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function resolveFragments(array $recordIDs): array
    {
        if ($this->signaturesPlugin->hide()) {
            return [];
        }
        return $this->signaturesPlugin->getUsersSignature($recordIDs);
    }
}
