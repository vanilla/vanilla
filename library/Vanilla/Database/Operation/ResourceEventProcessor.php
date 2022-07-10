<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Garden\EventManager;
use Garden\Events\GenericResourceEvent;
use Garden\Events\ResourceEvent;
use Garden\Events\ResourceEventLimitException;
use Vanilla\Database\Operation;
use Vanilla\Events\DirtyRecordTrait;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;

/**
 * Processor for creating and firing resource events from a pipeline model.
 */
class ResourceEventProcessor implements Processor {

    use DirtyRecordTrait;

    const META_NEW_INSERT_ID = 'newInsertID';

    /** @var int If more records than this are triggered at once then we will fail the request. */
    const MAX_UPDATE_LIMIT = 1000;

    /** @var EventManager */
    private $eventManager;

    /** @var \UserModel */
    private $userModel;

    /** @var int Maximum number of records that can be updated at once. */
    private $updateLimit = self::MAX_UPDATE_LIMIT;

    private $resourceEventClass = GenericResourceEvent::class;

    private $restrictedProperties = [];

    /**
     * DI.
     *
     * @param EventManager $eventManager
     * @param \UserModel $userModel
     */
    public function __construct(EventManager $eventManager, \UserModel $userModel) {
        $this->eventManager = $eventManager;
        $this->userModel = $userModel;
    }

    /**
     * @param string $resourceEventClass
     */
    public function setResourceEventClass(string $resourceEventClass): void {
        $this->resourceEventClass = $resourceEventClass;
    }

    /**
     * Clear the cache on certain operations.
     *
     * @param Operation $operation
     * @param callable $stack
     * @return mixed|void
     */
    public function handle(Operation $operation, callable $stack) {
        $operationType = $operation->getType();

        $this->validateUpdateLimit($operation);
        // Deletes have to be gathered before the actual record is deleted.
        $deleteEvents = $this->getDeleteResourceEvents($operation);

        // Process the stack so that we get a result.
        $result = $stack($operation);
        if ($operationType === Operation::TYPE_INSERT) {
            $operation->setMeta(self::META_NEW_INSERT_ID, $result);
        }

        // Inserts and updates have to be gathered after the records are inserted/updated.
        $insertUpdateEvents = $this->getInsertUpdateResourceEvents($operation);
        if (!$insertUpdateEvents &&
            ($operationType == Operation::TYPE_UPDATE)
        ) {
            $this->stashDirtyRecords($operation);
        }

        $allEvents = array_merge($deleteEvents, $insertUpdateEvents);
        foreach ($allEvents as $event) {
            $this->eventManager->dispatch($event);
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getUpdateLimit(): int {
        return $this->updateLimit;
    }

    /**
     * @param int $updateLimit
     */
    public function setUpdateLimit(int $updateLimit): void {
        $this->updateLimit = $updateLimit;
    }

    /**
     * @param Operation $operation
     */
    private function validateUpdateLimit(Operation $operation) {
        if (!in_array($operation->getType(), [Operation::TYPE_UPDATE, Operation::TYPE_DELETE])) {
            // We only care about updates to the tables.
            return;
        }

        $model = $operation->getCaller();
        if ($model === null) {
            // We can only process actions that have a model caller.
            return;
        }

        $actualLimit = $this->getUpdateLimit();
        $affectedRowCount = $model->selectSingle($operation->getWhere(), [
            Model::OPT_SELECT => 'count(*) as count',
            PipelineModel::OPT_RUN_PIPELINE => false, // If we run the pipeline, it can polluate the pipelines primary action.
        ])['count'] ?? 0;

        if ($affectedRowCount > $actualLimit) {
            throw new ResourceEventLimitException($actualLimit, $affectedRowCount);
        }
    }

    /**
     * Try to create and dispatch a resource event based on a database operation.
     *
     * @param Operation $operation
     *
     * @return ResourceEvent[]
     */
    private function getDeleteResourceEvents(Operation $operation): array {
        if ($operation->getType() !== Operation::TYPE_DELETE) {
            return [];
        }

        return $this->createResourceEvents($operation, ResourceEvent::ACTION_DELETE);
    }

    /**
     * Try to create and dispatch a resource event based on a database operation.
     *
     * @param Operation $operation
     *
     * @return ResourceEvent[]
     */
    private function getInsertUpdateResourceEvents(Operation $operation): array {
        $operationType = $operation->getType();
        if (!in_array($operationType, [Operation::TYPE_INSERT, Operation::TYPE_UPDATE])) {
            // This only handles inserts and updates.
            return [];
        }

        $action = $operationType === Operation::TYPE_INSERT ? ResourceEvent::ACTION_INSERT : ResourceEvent::ACTION_UPDATE;
        $properties = $operation->getSet();
        $shouldResource = true;

        if ($operationType === Operation::TYPE_UPDATE) {
            $shouldResource = $this->shouldResourceEvent($properties);
        }

        $resourceEvents = [];
        if ($shouldResource) {
            $resourceEvents = $this->createResourceEvents($operation, $action);
        }

        return $resourceEvents;
    }

    /**
     * Check if a property is restricted before firing resource event.
     *
     * @param array|string $properties
     * @return bool
     */
    public function shouldResourceEvent($properties): bool {
        $shouldResourceEvent = true;

        if (is_array($properties)) {
            $propertyNames = array_keys($properties);
            foreach ($propertyNames as $propertyName) {
                $fieldIsValid = !str_contains($propertyName, 'Count')
                    && !in_array($propertyName, $this->getRestrictedProperties());
                if (!$fieldIsValid) {
                    $shouldResourceEvent = false;
                    break;
                }
            }
        }

        if (is_string($properties)) {
            $fieldIsValid = (
                !in_array($properties, $this->getRestrictedProperties()) &&
                !str_contains($properties, 'Count')
            );
            if (!$fieldIsValid) {
                $shouldResourceEvent = false;
            }
        }

        return $shouldResourceEvent;
    }

    /**
     * Create and dispatch resource events for a current operation.
     *
     * If an operation affects multiple resources, multiple events will be fired.
     *
     * @param Operation $operation
     * @param string $action
     *
     * @return ResourceEvent[]
     */
    private function createResourceEvents(Operation $operation, string $action): array {
        $events = [];
        $model = $operation->getCaller();
        if ($model === null) {
            // We can only process actions that have a model caller.
            return [];
        }
        $recordType = $model->getTable();

        $where = $operation->getWhere();
        $metaID = $operation->getMeta(self::META_NEW_INSERT_ID, null);
        if ($metaID !== null) {
            $where = array_merge($where, $model->primaryWhere($metaID));
        }
        $rows = $model->select($where, $operation->getOptions());
        $currentUserFragment =  $this->userModel->currentFragment();

        $resourceEventClass = $this->resourceEventClass;

        foreach ($rows as $row) {
            $event = new $resourceEventClass(
                $action,
                [
                    $recordType => $row,
                ],
                $currentUserFragment
            );
            if ($event instanceof GenericResourceEvent) {
                $event->setType($recordType);
            }
            $events[] = $event;
        }
        return $events;
    }

    /**
     * Set a resources restricted properties.
     *
     * @param array $restrictedProperties
     */
    public function setRestrictedProperties(array $restrictedProperties) {
        $this->restrictedProperties = $restrictedProperties;
    }

    /**
     * Get a Resources restricted properties.
     *
     * @return array
     */
    public function getRestrictedProperties(): array {
        return $this->restrictedProperties;
    }

    /**
     * Stash dirty records to be handled later.
     *
     * @param Operation $operation
     */
    private function stashDirtyRecords(Operation $operation): void {
        $resourceType = $operation->getMeta("resourceType");
        $resourceIDs = $operation->getMeta("resourceIDs");

        if ($resourceType && $resourceIDs) {
            if (is_array($resourceIDs)) {
                foreach ($resourceIDs as $resourceID) {
                    $this->addDirtyRecord($resourceType, $resourceID);
                }
            } else {
                $this->addDirtyRecord($resourceType, $resourceIDs);
            }
        }
    }
}
