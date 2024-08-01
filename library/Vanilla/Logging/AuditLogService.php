<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

/**
 * Service for registering event classes so we can format them later.
 */
class AuditLogService
{
    /** @var array<class-string<AuditLogEventInterface>>  */
    private array $eventClasses;

    /**
     * @param string $eventClass
     * @return void
     */
    public function registerEventClass(string $eventClass): void
    {
        $this->eventClasses[] = $eventClass;
        $this->eventClasses = array_unique($this->eventClasses);
    }

    /**
     * @param string[] $eventClasses
     * @return void
     */
    public function registerEventClasses(array $eventClasses): void
    {
        foreach ($eventClasses as $eventClass) {
            $this->registerEventClass($eventClass);
        }
    }

    /**
     * Get all event classes.
     *
     * @return \class-string<AuditLogEventInterface>[]
     */
    public function getEventClasses(): array
    {
        return $this->eventClasses;
    }

    /**
     * Get the class of an {@link AuditLogEventInterface} based on an event type and it's context.
     *
     * @param string $eventType
     * @param array $context
     * @param array $meta
     *
     * @return class-string<AuditLogEventInterface>|null
     */
    public function findClass(string $eventType, array $context, array $meta): ?string
    {
        if (isset($meta["eventClass"]) && class_exists($meta["eventClass"])) {
            return $meta["eventClass"];
        }
        foreach ($this->eventClasses as $eventClass) {
            if ($eventClass::canFormatAuditMessage($eventType, $context, $meta)) {
                return $eventClass;
            }
        }

        // Fallback
        return null;
    }

    /**
     * Format an audit log message using it's specified {@link AuditLogEventInterface}
     *
     * @param string $eventType
     * @param array $context
     * @param array $meta
     *
     * @return string
     */
    public function formatEventMessage(string $eventType, array $context, array $meta): string
    {
        $class = $this->findClass($eventType, $context, $meta);
        if ($class === null) {
            return $eventType;
        }

        return $class::formatAuditMessage($eventType, $context, $meta);
    }
}
