<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Trigger;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\Logger;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;

abstract class AutomationTrigger
{
    const TRIGGER_TYPE = "triggerType";
    const TRIGGER_NAME = "name";
    const TRIGGER_ACTIONS = "triggerActions";
    const TRIGGER_CONTENT_TYPE = "contentType";

    protected string $longRunnerMethod = "runAutomationRule";
    protected Logger $logger;

    /**
     * Get the trigger record
     *
     * @return array
     */
    public function getRecord(): array
    {
        return self::getBaseSchemaArray();
    }

    /**
     * Get base schema array
     *
     * @return array
     */
    public static function getBaseSchemaArray(): array
    {
        $actions = [];
        foreach (static::getActions() as $action) {
            if (class_exists($action)) {
                $actions[] = $action::getType();
            }
        }

        return [
            self::TRIGGER_TYPE => static::getType(),
            self::TRIGGER_NAME => static::getName(),
            self::TRIGGER_ACTIONS => $actions,
            self::TRIGGER_CONTENT_TYPE => static::getContentType(),
        ];
    }

    /**
     * @inheridoc
     */
    public function getLongRunnerMethod(): string
    {
        return $this->longRunnerMethod;
    }

    /**
     * @inheridoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable
    {
        return yield;
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        // Do nothing by default.
        return;
    }

    /**
     * Get logger class
     *
     * @return Logger
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function getLogger(): Logger
    {
        if (!isset($this->logger)) {
            $this->logger = \Gdn::getContainer()->get(Logger::class);
        }
        return $this->logger;
    }

    /**
     * Get the trigger schema
     *
     * @return Schema
     */
    abstract static function getSchema(): Schema;

    /**
     * Get the trigger description
     *
     * @return string
     */
    abstract static function getName(): string;

    /**
     * Get the trigger actions
     *
     * @return array<AutomationAction::class>
     */
    abstract static function getActions(): array;

    /**
     * Get the trigger name
     *
     * @return string
     */
    abstract static function getType(): string;

    /**
     * Trigger the total record count to process
     *
     * @param array $where
     * @return int
     */
    abstract public function getRecordCountsToProcess(array $where): int;

    /**
     * Get the trigger content type
     *
     * @return string
     */
    abstract static function getContentType(): string;
}
