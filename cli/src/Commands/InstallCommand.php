<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Cli\Utils\ShellProfile;
use Vanilla\Cli\Utils\SimpleScriptLogger;

/**
 * Install command.
 */
class InstallCommand implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const BIN_DIR = PATH_ROOT . "/cli/bin";

    /**
     * The logger instance.
     *
     * @var SimpleScriptLogger
     */
    protected $logger;

    /**
     * Install the vnla utility onto your $PATH. Only installs to /usr/local/bin.
     * If you need to install somewhere else, symlink it yourself.
     */
    public function install()
    {
        if (self::isInstalled()) {
            $this->logger->success("vnla is already installed.");
            return;
        }

        ShellProfile::prependPath(self::BIN_DIR);
    }

    /**
     * Lint changed PHP code.
     */
    public static function isInstalled(): bool
    {
        return ShellProfile::hasInPath(self::BIN_DIR);
    }
}
