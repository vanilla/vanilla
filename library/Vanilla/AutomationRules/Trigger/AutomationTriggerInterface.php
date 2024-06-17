<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Trigger;

use Garden\Schema\Schema;

/**
 * Interface AutomationTriggerInterface
 */
interface AutomationTriggerInterface
{
    /**
     * Get the trigger record
     *
     * @return array
     */
    public function getRecord(): array;

    /**
     * Get the trigger name
     *
     * @return string
     */
    public static function getType(): string;

    /**
     * Get the trigger description
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Get the trigger actions
     *
     * @return array
     */
    public static function getActions(): array;

    /**
     * Provide the schema for validation of post and patch endpoints
     *
     * @param Schema $schema
     * @return void
     */
    public static function getPostPatchSchema(Schema &$schema): void;

    /**
     * Trigger the total record count to process
     *
     * @param array $where
     * @return int
     */
    public function getRecordCountsToProcess(array $where): int;

    /**
     * @param int|string $lastRecordId
     * @param array $where
     * @return \Generator
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable;
}
