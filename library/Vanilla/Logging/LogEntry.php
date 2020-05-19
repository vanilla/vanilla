<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

/**
 * Simple class for representing a log entry.
 */
class LogEntry {

    /** @var string */
    private $level;

    /** @var string */
    private $message;

    /** @var array */
    private $context;

    /**
     * Setup the log entry.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function __construct(string $level, string $message, array $context = []) {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Get the configured logging level.
     *
     * @return string
     */
    public function getLevel(): string {
        return $this->level;
    }

    /**
     * Get the configured message.
     *
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * Get the configured context.
     *
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }
}
