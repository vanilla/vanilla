<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Utils;

use Symfony\Component\Process\Process;

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

    const ESCAPE_MAPPINGS = [
        "red" => self::ESCAPE_RED,
        "green" => self::ESCAPE_GREEN,
        "yellow" => self::ESCAPE_YELLOW,
        "purple" => self::ESCAPE_PURPLE,
        "cyan" => self::ESCAPE_CYAN,
    ];

    public static bool $isVerbose = false;

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
        if (self::$isVerbose) {
            $this->logInternal($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        $this->logInternal($message, $context);
    }

    /**
     * Run a process logging it's output.
     *
     * @param Process $process The process to run.
     * @param bool|callable(string $line): ?string $makeOutputLine
     * - If true: Logs all output, in TTY mode if possible.
     * - If false: Logs ... in place of output in an indeterminate progress.
     * - If callable: Calls the callable with each line of output, outputs if true is returned.
     * - In verbose mode the callable is ignored and it's as if `true` was passed.
     *
     * @return void
     */
    public function runProcess(Process $process, $makeOutputLine = false)
    {
        if (!$makeOutputLine) {
            $makeOutputLine = function () {
                return null;
            };
        }
        if ($makeOutputLine === true || self::$isVerbose) {
            // In verbose mode we try to use tty mode.
            $process->setTty(stream_isatty(STDOUT));
        }
        try {
            $countDotsOnLine = 0;
            $process->mustRun(function ($type, $data) use (&$countDotsOnLine, $makeOutputLine) {
                if (self::$isVerbose) {
                    // In verbose mode we output everything directly.
                    echo $data;
                    return;
                }
                $lines = explode("\n", $data);
                $lines = array_unique($lines);
                foreach ($lines as $line) {
                    $outputLine = $makeOutputLine === true ? $line : $makeOutputLine($line);
                    if ($outputLine !== null) {
                        if ($countDotsOnLine > 0) {
                            echo "\n";
                            $countDotsOnLine = 0;
                        }
                        $this->info($outputLine);
                    } else {
                        if ($countDotsOnLine > 0) {
                            usleep(1000 * 50);
                        }
                        if ($countDotsOnLine > 80) {
                            echo "\n";
                            $countDotsOnLine = 0;
                        }
                        $countDotsOnLine++;
                        echo ".";
                    }
                }
            });
        } finally {
            if ($countDotsOnLine >= 0) {
                // Make sure we move to a newline.
                echo "\n";
            }
        }
    }

    /**
     * Internal logging method.
     *
     * @param string $message
     * @param array $context
     */
    private function logInternal($message, array $context = [])
    {
        $isTty = stream_isatty(STDOUT);
        $countNewLines = $context[self::CONTEXT_LINE_COUNT] ?? 1;
        $escapeSequence = $context[self::CONTEXT_ESCAPE_SEQUENCE] ?? null;

        $newlines = "";
        for ($i = 0; $i < $countNewLines; $i++) {
            $newlines .= "\n";
        }
        $result = "$message$newlines";

        if ($escapeSequence !== null && $isTty) {
            $result = "\033[{$escapeSequence}m{$result}\033[0m";
        }

        // Now replace placeholders with escape sequences
        $searches = [];
        $replacements = [];
        foreach (self::ESCAPE_MAPPINGS as $keyword => $escapeSequence) {
            $searches[] = "<$keyword>";
            $replacements[] = $isTty ? "\033[{$escapeSequence}m" : "";
            $searches[] = "</$keyword>";
            $replacements[] = $isTty ? "\033[0m" : "";
            $searches[] = "</ $keyword>";
            $replacements[] = $isTty ? "\033[0m" : "";
        }
        $result = str_replace($searches, $replacements, $result);

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
