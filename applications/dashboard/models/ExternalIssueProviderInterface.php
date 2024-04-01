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
interface ExternalIssueProviderInterface
{
    // const TYPE_NAME {should match the name of the issue type};

    /**
     * Create a new issue in the external service and return the saved associated attachment data.
     *
     * @param string $recordType
     * @param int $recordID
     * @param array $issueData
     * @return array
     */
    public function makeNewIssue(string $recordType, int $recordID, array $issueData): array;

    /**
     * Sync issue data from the external issue tracker and return the saved associated attachment data.
     *
     * @param array $attachment
     * @return array
     */
    public function syncIssue(array $attachment): array;

    /**
     * The schema for required special posting fields.
     *
     * @return Schema
     */
    public function issuePostSchema(): \Garden\Schema\Schema;

    /**
     * The schema for the full issue data.
     *
     * @return Schema
     */
    public function fullIssueSchema(): \Garden\Schema\Schema;

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
     * @return Schema
     */
    public function getHydratedFormSchema(string $recordType, int $recordID): Schema;
    /**
     * Verify that the user is authorized to use this provider.
     *
     * @param $user
     * @return bool
     */
    public function validatePermissions($user): bool;

    /**
     * Get the types of records that can be used with this provider.
     *
     * @return array
     */
    public function getRecordTypes(): array;

    /**
     * Return the catalog of the provider to be process by the front-end.
     *
     * @return array
     */
    public function getCatalog(): array;

    /**
     * Set project ID for integration
     *
     * @param int|null $projectID
     * @return void
     */
    public function setProjectID($projectID): void;

    /**
     * Set Issue Type ID for integration
     *
     * @param int|null $issueTypeID
     * @return void
     */
    public function setIssueTypeID($issueTypeID): void;

    /**
     * Get the time in milliseconds to wait before refreshing the external data.
     *
     * @return int
     */
    public function getRefreshTime(): int;

    //    abstract public function getOptions(string $recordType, array $record): array;
}
