<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use Garden\Schema\Schema;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;

/**
 * Interface AutomationActionInterface
 */

interface AutomationActionInterface
{
    /**
     * Get the action record
     *
     * @return array
     */
    public function getRecord(): array;

    /*
     *  Get the action type
     *
     * @return string
     */
    public static function getType(): string;

    /**
     * Get the action description
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Get the action triggers
     *
     * @return array
     */
    public static function getTriggers(): array;

    /**
     * Execute the long runner action
     * @param array $actionValue Action value.
     * @param array $object Object to perform on.
     * @return bool
     */
    public function executeLongRunner(array $actionValue, array $object): bool;

    /**
     * Provide the schema for validation of post and patch endpoints
     *
     * @param Schema $schema
     * @return void
     */
    public static function getPostPatchSchema(Schema &$schema): void;

    /**
     * Trigger the long runner rule
     *
     * @param AutomationTrigger $triggerClass
     * @param bool $firstRun
     * @return array
     */
    public function triggerLongRunnerRule(AutomationTrigger $triggerClass, bool $firstRun = false): array;

    /**
     * Expand log data into a format that can be used to display in the log
     *
     * @param array $logData
     * @return string
     */
    public function expandLogData(array $logData): string;
}
