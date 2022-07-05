<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Utils;

/**
 * Minimal logger for scripts.
 *
 * This should probably be extended in the future. Right now we don't differentiate the different levels very well.
 */
class SimpleScriptLogger implements \Psr\Log\LoggerInterface
{
    const CONTEXT_LINE_COUNT = "lineCount";
    const CONTEXT_ESCAPE_SEQUENCE = "escapeSequence";

    const ESCAPE_RED = "0;31";
    const ESCAPE_GREEN = "0;32";
    const ESCAPE_YELLOW = "1;33";
    const ESCAPE_PURPLE = "1;35";
    const ESCAPE_CYAN = "0;36";

    /**
     * @inheritdoc
     */
    public function emergency($message, array $context = [])
    {
        $this->logInternal($message, $context + [self::CONTEXT_ESCAPE_SEQUENCE => self::ESCAPE_PURPLE]);
    }

    /**
     * @inheritdoc
     */
    public function alert($message, array $context = [])
    {
        $this->logInternal($message, $context + [self::CONTEXT_ESCAPE_SEQUENCE => self::ESCAPE_YELLOW]);
    }

    /**
     * Log a success message.
     *
     * @param string $message
     * @param array $context
     */
    public function success($message, array $context = [])
    {
        $this->logInternal($message, $context + [self::CONTEXT_ESCAPE_SEQUENCE => self::ESCAPE_GREEN]);
    }

    /**
     * Log a title.
     *
     * @param string $message
     * @param array $context
     */
    public function title(string $message, array $context = [])
    {
        $this->logInternal(
            "\n======  $message  ======",
            [self::CONTEXT_ESCAPE_SEQUENCE => self::ESCAPE_CYAN] + $context
        );
    }

    /**
     * @inheritdoc
     */
    public function critical($message, array $context = [])
    {
        $this->logInternal($message, $context + [self::CONTEXT_ESCAPE_SEQUENCE => self::ESCAPE_RED]);
    }

    /**
     * @inheritdoc
     */
    public function warning($message, array $context = [])
    {
        $this->logInternal($message, $context + [self::CONTEXT_ESCAPE_SEQUENCE => self::ESCAPE_YELLOW]);
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = [])
    {
        $this->logInternal($message, $context + [self::CONTEXT_ESCAPE_SEQUENCE => self::ESCAPE_RED]);
    }

    /**
     * @inheritdoc
     */
    public function notice($message, array $context = [])
    {
        $this->logInternal($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = [])
    {
        $this->logInternal($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = [])
    {
        $this->logInternal($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        $this->logInternal($message, $context);
    }

    /**
     * Internal logging method.
     *
     * @param string $message
     * @param array $context
     */
    private function logInternal($message, array $context = [])
    {
        $countNewLines = $context[self::CONTEXT_LINE_COUNT] ?? 1;
        $escapeSequence = $context[self::CONTEXT_ESCAPE_SEQUENCE] ?? null;

        $newlines = "";
        for ($i = 0; $i < $countNewLines; $i++) {
            $newlines .= "\n";
        }
        $result = "$message$newlines";

        if ($escapeSequence !== null) {
            $result = "\033[${escapeSequence}m${result}\033[0m";
        }
        echo $result;
    }

    /**
     * Prompt yes or no to continue.
     *
     * @param string $prompt
     */
    public function promptContinue(string $prompt)
    {
        $result = readline($prompt . " (y\\n): ");
        if (strpos(strtolower($result), "y") === false) {
            $this->error("Exiting.");
            die(1);
        }
    }
}
