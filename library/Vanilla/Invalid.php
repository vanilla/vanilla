<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;


/**
 * Represents an invalid validation result.
 */
class Invalid {
    /**
     * @var string
     */
    private $messageCode;

    /**
     * Invalid constructor.
     *
     * @param string $messageCode The message translation code.
     */
    public function __construct(string $messageCode) {
        $this->messageCode = $messageCode;
    }

    /**
     * Get the error message translation code.
     *
     * @return string Returns the message code.
     */
    public function getMessageCode(): string {
        return $this->messageCode;
    }

    /**
     * A default instance with no error message.
     *
     * Use this instance like a singleton if you don't need a custom error message.
     *
     * @return Invalid Returns an invalid value.
     */
    public static function emptyMessage() {
        static $value;
        if ($value === null) {
            $value = new Invalid('');
        }

        return $value;
    }
}
