<?php

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;

/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
interface ExternalIssueProviderInterface
{
    // const SOURCE_NAME {should match the name of the plugin};

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
     * Get the source name of the provider.
     *
     * @return string
     */
    public function getSourceName(): string;

    //    abstract public function getOptions(string $recordType, array $record): array;
}
