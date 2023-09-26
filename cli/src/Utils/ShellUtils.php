<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Utils;

/**
 * Utilities for scripts.
 */
final class ShellUtils
{
    const YESSES = ["y", "yes"];

    /**
     * Execute an external command and return its output.
     *
     * @param string $cmdFormat
     * @param array $args
     * @param ?string $errorMessage
     * @param ?SimpleScriptLogger $logger
     * @return array
     */
    public static function command(
        string $cmdFormat,
        array $args = [],
        ?string $errorMessage = null,
        ?SimpleScriptLogger $logger = null
    ): array {
        $args = array_map("escapeshellarg", $args);
        $cmd = sprintf($cmdFormat, ...$args);

        if ($logger instanceof SimpleScriptLogger) {
            $logger->debug($cmd);
        }

        exec($cmd, $output, $resultCode);

        if ($resultCode !== 0) {
            throw new ShellException($errorMessage ?? "An error has occurred.");
        }

        return $output ?: [];
    }

    /**
     * Run a shell comamnd or exit if it fails.
     *
     * @param string $command The command shell out to.
     * @param string $message The message to throw in the exception.
     *
     * @return string The output of the script.
     */
    public static function shellOrThrow(
        string $command,
        string $message = "An error was encountered while running."
    ): string {
        $outputLine = system($command, $code);
        if ($code !== 0) {
            throw new ShellException($message, $code);
        }
        echo "\n";

        return $outputLine;
    }

    /**
     * Run a shell comamnd and call a callback if it fails.
     *
     * @param string $command
     * @param callable $callback
     */
    public static function shellOrCallback(string $command, callable $callback)
    {
        system($command, $result);
        if ($result !== 0) {
            call_user_func($callback);
        }
    }

    /**
     * Prompt a user for an a choice.
     *
     * @param string $prompt The text to display before prompting.
     * @param array $choices Choices to select from. The key is the keyboard key to look for and the value is the label of the choice.
     * @returns string The key of the selected choice.
     */
    public static function promptChoices(string $prompt, array $choices)
    {
        foreach ($choices as $key => $label) {
            echo "$key = $label\n";
        }
        while (true) {
            $choice = self::promptString($prompt);
            if (array_key_exists($choice, $choices)) {
                echo "\n";
                return $choice;
            }
        }
    }

    /**
     * Prompt a user for an arbitrary string.
     *
     * @param string $prompt
     * @return string
     */
    public static function promptString(string $prompt): string
    {
        echo "$prompt ";
        return rtrim(fgets(STDIN));
    }

    /**
     * Prompt a user for a password string.
     *
     * @param string $prompt
     * @return string
     */
    public static function promptPassword(string $prompt): string
    {
        echo "$prompt ";
        system("stty -echo");
        $password = rtrim(fgets(STDIN));
        system("stty echo");
        return $password;
    }

    /**
     * Prompt a user for a pattern contained in a string.
     * If the pattern cannot be found, the script exits.
     *
     * @param string $prompt
     * @param string $pattern
     * @return mixed
     */
    public static function promptPreg(string $prompt, string $pattern)
    {
        echo "$prompt ";
        $line = rtrim(fgets(STDIN));
        if (!preg_match($pattern, $line, $matches)) {
            echo "No match found. Exiting.\n";
            exit();
        }
        return $matches;
    }

    /**
     * Prompt a user for yes or no, exiting on no.
     *
     * @param string $prompt
     * @param bool $exit
     * @return bool
     */
    public static function promptYesNo(string $prompt, bool $exit = false): bool
    {
        $logger = new SimpleScriptLogger();
        $logger->info("$prompt (y/n)", [
            SimpleScriptLogger::CONTEXT_LINE_COUNT => 0,
        ]);
        $line = strtolower(trim(fgets(STDIN)));
        $isYes = in_array($line, self::YESSES);
        if (!$isYes && $exit) {
            echo "Exiting!\n";
            exit();
        }
        return $isYes;
    }
}
