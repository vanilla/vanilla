<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;

/**
 * Interface for external issue providers.
 */
interface AttachmentProviderInterface
{
    // const TYPE_NAME {should match the name of the issue type};

    const ESCALATION_DELAY_UNITS = ["minute", "hour", "day", "week", "month", "year"];

    /**
     * Create a new issue in the external service and return the saved associated attachment data.
     *
     * @param string $recordType
     * @param int $recordID
     * @param array $issueData
     * @return array
     */
    public function createAttachment(string $recordType, int $recordID, array $issueData): array;

    /**
     * Given a list of existing attachmentIDs, refresh the data for these attachments.
     *
     * @param array $attachmentRows
     */
    public function refreshAttachments(array $attachmentRows): array;

    /**
     * Normalize an attachment of our own type for display.
     *
     * @param array $attachment
     *
     * @return array
     */
    public function normalizeAttachment(array $attachment): array;

    /**
     * Get the type name of the provider.
     *
     * @return string
     */
    public function getTypeName(): string;

    /**
     * Get the form schema for creating the external issue with fields dynamically populated from the record.
     *
     * @param string $recordType
     * @param int $recordID
     * @param array $args
     *
     * @return Schema
     */
    public function getHydratedFormSchema(string $recordType, int $recordID, array $args): Schema;

    /**
     * Verify that the user is authorized to use this provider.
     *
     * @return bool
     */
    public function hasPermissions(): bool;

    /**
     * Get the types of records that can be used with this provider.
     *
     * @return array
     */
    public function getRecordTypes(): array;

    /**
     * Get a label code for the button that instantiates a new attachment.
     *
     * @return string
     */
    public function getCreateLabelCode(): string;

    /**
     * Get a label code for submit on the form for this attachment.
     *
     * @return string
     */
    public function getSubmitLabelCode(): string;

    /**
     * Get a label code for the title on the form for this attachment.
     *
     * @return string
     */
    public function getTitleLabelCode(): string;

    /**
     * Get a label code for the external ID of an instance of the attachment.
     *
     * @return string
     */
    public function getExternalIDLabelCode(): string;

    /**
     * Get the name of the @vanilla/icons icon for this attachment type.
     *
     * @return string
     */
    public function getLogoIconName(): string;

    /**
     * Get the time in seconds to wait before refreshing the external data.
     *
     * @return int
     */
    public function getRefreshTimeSeconds(): int;

    /**
     * If a user can escalate their own post to this provider.
     *
     * @return bool
     */
    public function canEscalateOwnPost(): bool;

    /**
     * Get units used to calculate time span before a user can escalate their own post.
     * Must be one of `AttachmentProviderInterface::ESCALATION_DELAY_UNITS`.
     *
     * @return string|null
     */
    public function getEscalationDelayUnit(): ?string;

    /**
     * Get length of time span before a user can escalate their own post.
     * Should be used with AttachmentProviderInterface::getEscalationDelayUnit()`.
     *
     * @return int
     */
    public function getEscalationDelayLength(): int;

    /**
     * If the current user can view a specific attachment record.
     *
     * @param array $attachment
     * @return bool
     */
    public function canViewAttachment(array $attachment): bool;

    /**
     * If the current user can create an attachment for a specific recordType and recordID.
     *
     * @param string $recordType
     * @param int $recordID
     * @return bool
     */
    public function canCreateAttachmentForRecord(string $recordType, int $recordID): bool;

    /**
     * Get the provider name.
     */
    public function getProviderName(): string;
}
