<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli;

use Garden\Cli\Application\CliApplication;
use Garden\Container\Container;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Cli\Commands;
use Vanilla\Cli\Utils\SimpleScriptLogger;

/**
 * Entrypoint for the vanilla-scripts cli.
 */
class VanillaCli extends CliApplication {

    /**
     * Configure the commands.
     */
    protected function configureCli(): void {
        parent::configureCli();

        $this->addMethod(Commands\BackportCommand::class, 'backport', [self::OPT_SETTERS => true]);
        $this->addMethod(Commands\InstallCommand::class, 'install');
        $this->addMethod(Commands\VanillaCacheCommand::class, 'clearCaches', [self::OPT_SETTERS => true]);
        $this->addMethod(Commands\LintCommand::class, 'lint', [self::OPT_SETTERS => true]);
    }

    /**
     * @return Container
     */
    protected function createContainer(): Container {
        $container = parent::createContainer();

        $container
            ->rule(LoggerAwareInterface::class)
            ->addCall('setLogger')
            ->rule(LoggerInterface::class)
            ->setClass(SimpleScriptLogger::class)
        ;

        return $container;
    }
}
