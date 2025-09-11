<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Web\AbstractApiExpander;
use Psr\Log\LoggerInterface;

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
    public function __construct(SignaturesPlugin $signaturesPlugin, private LoggerInterface $logger)
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
     * @inheritdoc
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
        try {
            if ($this->signaturesPlugin->hide()) {
                return [];
            }
            return $this->signaturesPlugin->getUsersSignature($recordIDs);
        } catch (Exception $e) {
            // Log the error and carry on.
            $this->logger->error("Error resolving user signatures: " . $e->getMessage());
            return [];
        }
    }
}
