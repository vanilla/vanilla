<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Utils;

use Vanilla\Cli\Exception\UnsupportedShellException;

/**
 * Shell profile utils.
 *
 * Based off the utils in homebrew.
 * https://github.com/Homebrew/brew/blob/master/Library/Homebrew/utils/shell.rb#L74
 */
class ShellProfile
{
    public const SUPPORTED_SHELLS = [self::BASH, self::FISH, self::SH, self::ZSH];
    public const BASH = "bash";
    public const FISH = "fish";
    public const SH = "sh";
    public const ZSH = "zsh";

    private const SHELL_PROFILE_MAP = [
        "bash" => "~/.bash_profile",
        "fish" => "~/.config/fish/config.fish",
        "sh" => "~/.profile",
        "zsh" => "~/.zshrc",
    ];

    /**
     * Prepend a path into the user's $PATH.
     *
     * @param string $path The path to prepend.
     *
     * @throws UnsupportedShellException If the shell isn't supported.
     */
    public static function prependPath(string $path)
    {
        $shell = self::getShell();
        $config = self::getShellConfigPath();
        $prependCommand = self::getPrependCommand($shell, $path);

        file_put_contents($config, $prependCommand . PHP_EOL, FILE_APPEND | LOCK_EX);
        self::printShellReloadInstructions();
    }

    /**
     * Reload the current shell profile.
     */
    public static function printShellReloadInstructions()
    {
        $config = self::getShellConfigPath();
        $logger = new SimpleScriptLogger();
        $logger->success("Added vnla binary path into your path using the following config:");
        $logger->info($config);
        $logger->warning(
            "\nFor the changes to take effect you will need to restart your shell or reload your config with:"
        );
        $logger->info("source $config");
    }

    /**
     * Check if some path is in our path environment.
     *
     * @param string $pathToCheck
     *
     * @return bool
     */
    public static function hasInPath(string $pathToCheck): bool
    {
        $envPath = getenv("PATH") ?: "";
        $splitEnvPieces = explode(":", $envPath);
        return in_array($pathToCheck, $splitEnvPieces);
    }

    /**
     * @return string
     */
    private static function getShellConfigPath(): string
    {
        $shell = self::getShell();
        $mapped = self::SHELL_PROFILE_MAP[$shell];
        return str_replace("~", getenv("HOME"), $mapped);
    }

    /**
     * Get the current shell.
     *
     * @return string The shell name.
     */
    private static function getShell(): string
    {
        $envValue = (string) getenv("SHELL") ?: "";
        $basename = basename($envValue);
        # handle possible version suffix like `zsh-5.2`
        $normalized = strtolower($basename);
        $normalized = preg_replace("/-.*\z/", "", $normalized);

        if (!in_array($normalized, self::SUPPORTED_SHELLS)) {
            throw new UnsupportedShellException($normalized);
        }
        return $normalized;
    }

    /**
     * Get a command to prepend into the shell config.
     *
     * @param string $shell The shell in use.
     * @param string $newPath The path to prepend.
     *
     * @return string
     *
     * @throws UnsupportedShellException If the shell isn't supported.
     */
    private static function getPrependCommand(string $shell, string $newPath): string
    {
        switch ($shell) {
            case self::FISH:
                return "fish_add_path $newPath";
            case self::BASH:
            case self::SH:
            case self::ZSH:
                return "export PATH=\"$newPath:\$PATH\"";
            default:
                throw new UnsupportedShellException($shell);
        }
    }
}
