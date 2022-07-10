<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

use Vanilla\Events\EventAction;
use Vanilla\Utility\ModelUtils;

/**
 * An event affecting a specific resource.
 */
abstract class ResourceEvent implements \JsonSerializable {

    /** A resource has been removed. */
    public const ACTION_DELETE = EventAction::DELETE;

    /** A resource has been created. */
    public const ACTION_INSERT = EventAction::ADD;

    /** An existing resource has been updated. */
    public const ACTION_UPDATE = EventAction::UPDATE;

    /** @var string */
    protected $action;

    /** @var array */
    protected $payload;

    /** @var array */
    protected $sender;

    /** @var string */
    protected $type;

    /** @var array $apiParams */
    protected $apiParams;

    /**
     * Create the event.
     *
     * @param string $action
     * @param array $payload
     * @param array|object|null $sender
     */
    public function __construct(string $action, array $payload, $sender = null) {
        $this->action = $action;
        $this->payload = $payload;
        $this->apiParams = [
            'expand' => [ModelUtils::EXPAND_CRAWL],
        ];
        $this->sender = $sender;
        $this->type = $this->typeFromClass();
    }

    /**
     * Get the event action.
     *
     * @return string
     */
    public function getAction(): string {
        return $this->action;
    }
    /**
     * Get the event payload.
     *
     * @return array|null
     */
    public function getPayload(): ?array {
        return $this->payload;
    }

    /**
     * Set the event payload.
     *
     * @param array $payload The key => value pairs to set on the payload.
     */
    public function setPayload(array $payload): void {
        $this->payload = $payload;
    }

    /**
     * Get the event resource api params.
     *
     * @return array|null
     */
    public function getApiParams(): ?array {
        return $this->apiParams;
    }

    /**
     * Set event resource api additional params.
     *
     * @param array $params
     */
    public function addApiParams(array $params) {
        $this->apiParams = array_merge($this->apiParams, $params);
    }

    /**
     * Get the entity responsible for triggering the event, if available.
     *
     * @return array|object|null
     */
    public function getSender() {
        return $this->sender;
    }

    /**
     * Get the event type.
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get a unique primary key for the record.
     *
     * @return string
     */
    public function getUniquePrimaryKey(): string {
        return $this->getType() . "ID";
    }

    /**
     * Get a unique primary key for the record.
     *
     * @return string|int
     */
    public function getUniquePrimaryKeyValue() {
        return $this->getPayload()[$this->getType()][$this->getUniquePrimaryKey()];
    }

    /**
     * Get the full name of the event.
     *
     * @return string
     */
    public function getFullEventName(): string {
        return $this->getType().'_'.$this->getAction();
    }

    /**
     * Derive the event type from the current class name.
     *
     * @return string
     */
    public static function typeFromClass(): string {
        $baseName = get_called_class();
        if (($namespaceEnd = strrpos($baseName, '\\')) !== false) {
            $baseName = substr($baseName, $namespaceEnd + 1);
        }
        $type = lcfirst(preg_replace('/Event$/', '', $baseName));
        return $type;
    }

    /**
     * Create a normalized variation of the record's payload.
     *
     * @return array A tuple of [string, int]
     */
    public function getRecordTypeAndID(): array {
        $recordType = $this->getType();

        if ($idKey = ($this->payload['documentIdField'] ?? false)) {
            $idKey = $this->$idKey;
        } else {
            $idKey = $this->type . 'ID';
        }

        $payloadRecord = $this->payload[$this->type] ?? $this->payload;
        $recordID = $payloadRecord['recordID'] ?? $payloadRecord[$idKey] ?? null;

        return [$recordType, $recordID];
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return [
            'type' => $this->type,
            'action' => $this->action,
            'payload' => $this->getPayload(),
        ];
    }

    /**
     * Get the API URL for the resource.
     *
     * @return string
     */
    public function getApiUrl() {
        [$recordType, $recordID] = $this->getRecordTypeAndID();
        return "/api/v2/{$recordType}s/$recordID";
    }

    /**
     * Convert to string.
     */
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
