<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Vanilla\Logger;

class TestLogger implements LoggerInterface {
    use LoggerTrait;

    /**
     * @var Logger
     */
    public $parent;

    /**
     * @var array
     */
    public $last = [null, null, null];

    /*
     * @var array
     */
    public $logs = [];

    public function __construct(Logger $parent = null, $level = Logger::DEBUG) {
        if (!isset($parent)) {
            $parent = new Logger();
        }
        $parent->addLogger($this, $level);
        $this->parent = $parent;
    }

    public static function replaceContext($format, $context = []) {
        $msg = preg_replace_callback('`({[^\s{}]+})`', function($m) use ($context) {
            $field = trim($m[1], '{}');
            if (array_key_exists($field, $context)) {
                return $context[$field];
            } else {
                return $m[1];
            }
        }, $format);
        return $msg;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array()) {
        $msg = $this->replaceContext($message, $context);
        $this->logs[] = "$level $msg";
        $this->last = [$level, $message, $context];
    }
}
