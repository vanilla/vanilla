<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\ShellProfile;

/**
 * Install command.
 */
class InstallCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;

    public const BIN_DIR = PATH_ROOT . "/cli/bin";

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName("install")->setDescription(
            "Install the vnla utility onto your \$PATH. Only installs to /usr/local/bin."
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (self::isInstalled()) {
            $this->logger()->success("vnla is already installed.");
            return Console\Command\Command::SUCCESS;
        }

        ShellProfile::prependPath(self::BIN_DIR);
        return Console\Command\Command::SUCCESS;
    }

    /**
     * Lint changed PHP code.
     */
    public static function isInstalled(): bool
    {
        return ShellProfile::hasInPath(self::BIN_DIR);
    }
}
