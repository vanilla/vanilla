<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

use Vanilla\Events\EventAction;

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

    /**
     * Create the event.
     *
     * @param string $action
     * @param array $payload
     * @param array $sender
     */
    public function __construct(string $action, array $payload, ?array $sender = null) {
        $this->action = $action;
        $this->payload = $payload;
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
     * Get the entity responsible for triggering the event, if available.
     *
     * @return array|null
     */
    public function getSender(): ?array {
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
    private function typeFromClass(): string {
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

        $idKey = $this->type . 'ID';
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
     * Convert to string.
     */
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
