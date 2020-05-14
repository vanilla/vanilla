<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

/**
 * An event affecting a specific resource.
 */
abstract class ResourceEvent {

    /** A resource has been removed. */
    public const ACTION_DELETE = "delete";

    /** A resource has been created. */
    public const ACTION_INSERT = "insert";

    /** An existing resource has been updated. */
    public const ACTION_UPDATE = "update";

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
        $class = get_called_class();
        if (($namespaceEnd = strrpos($class, '\\')) !== false) {
            $baseName = substr($class, $namespaceEnd + 1);
        } else {
            $baseName = $class;
        }
        $type = lcfirst(preg_replace('/Event$/', '', $baseName));
        return $type;
    }
}
