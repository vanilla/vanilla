<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * A test logger that collects all messages in an array.
 */
class TestLogger implements LoggerInterface {
    use LoggerTrait;

    /**
     * @var array
     */
    private $log = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array()) {
        $this->log[] = ['level' => $level, 'message' => $message] + $context;
    }

    /**
     * Get the log.
     *
     * @return array Returns the log.
     */
    public function getLog(): array {
        return $this->log;
    }

    /**
     * Set the log.
     *
     * @param array $log The new log array.
     * @return $this
     */
    public function setLog(array $log) {
        $this->log = $log;
        return $this;
    }

    /**
     * Search the log for a filter.
     *
     * @param array $filter The log filter.
     * @return array|null Returns the first found log entry or **null** if an entry isn't found.
     */
    public function search($filter = []) {
        foreach ($this->log as $item) {
            $found = true;
            foreach ($filter as $key => $value) {
                if (!array_key_exists($key, $item) || $item[$key] != $value) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Checks to see if the log has a message, doing a substring match.
     *
     * @param string $message
     * @return bool
     */
    public function hasMessage(string $message): bool {
        foreach ($this->log as $item) {
            if (strpos($item['message'], $message) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clear the log.
     */
    public function clear() {
        $this->log = [];
    }
}
