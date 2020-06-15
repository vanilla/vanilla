<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Psr\Log\LoggerInterface;
use Vanilla\Logger;

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
        $this->context = $context + [
            Logger::FIELD_EVENT => '',
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
        ];
    }

    /**
     * Create a new entry for an event.
     *
     * @param string $level
     * @param string $event
     * @param string $message
     * @param string $channel
     * @param array $context
     * @return self
     */
    public static function createEvent(
        string $level,
        string $event,
        string $message,
        string $channel = Logger::CHANNEL_APPLICATION,
        array $context = []
    ): self {
        $context[Logger::FIELD_EVENT] = $event;
        $context[Logger::FIELD_CHANNEL] = $channel;
        return new static($level, $message, $context);
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
     * Create a copy with a new level.
     *
     * @param string $level
     * @return self
     */
    public function withLevel(string $level): self {
        return new static($level, $this->getMessage(), $this->getContext());
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
     * Create a new copy with a different message.
     *
     * @param string $message
     * @return self
     */
    public function withMessage(string $message): self {
        return new static($this->getLevel(), $message, $this->getContext());
    }

    /**
     * Get the configured context.
     *
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * Create a new copy with a new context.
     *
     * @param array $context
     * @return self
     */
    public function withContext(array $context): self {
        return new static($this->getLevel(), $this->getMessage(), $context);
    }

    /**
     * Create a new copy changing a single context field.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function withContextItem(string $key, $value): self {
        $context = $this->context;
        $context[$key] = $value;
        return $this->withContext($context);
    }

    /**
     * Get the name of the event.
     *
     * @return string
     */
    public function getEvent(): string {
        return $this->context[Logger::FIELD_EVENT] ?? '';
    }

    /**
     * Create a new cospy with an event name.
     *
     * @param string $event
     * @return self
     */
    public function withEvent(string $event): self {
        return $this->withContextItem(Logger::FIELD_EVENT, $event);
    }

    /**
     * Get the name of the channel being logged to.
     *
     * @return string
     */
    public function getChannel(): string {
        return $this->context[Logger::FIELD_CHANNEL] ?? Logger::CHANNEL_APPLICATION;
    }

    /**
     * Create a new entry with a channel.
     *
     * @param string $channel
     * @return self
     */
    public function withChannel(string $channel): self {
        return $this->withContextItem(Logger::FIELD_CHANNEL, $channel);
    }

    /**
     * Log this entry.
     *
     * @param LoggerInterface $logger
     */
    public function log(LoggerInterface $logger) {
        $logger->log($this->getLevel(), $this->getMessage(), $this->getContext());
    }
}
