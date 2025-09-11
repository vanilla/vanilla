<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Vanilla\Cli\Docker\Service\AbstractLaravelService;

/**
 * vnla docker:SERVICE command
 */
class DockerLaravelCommand extends DockerContainerCommand
{
    const COMMAND_ARTISAN = "artisan";
    const COMMAND_RELOAD_QUEUE = "reload-queue";

    /**
     * Constructor.
     *
     * @param AbstractLaravelService $laravelService
     */
    public function __construct(private AbstractLaravelService $laravelService)
    {
        parent::__construct($this->laravelService);
    }

    /**
     * @inheritdoc
     */
    protected static function getSubCommands(): array
    {
        return array_merge(parent::getSubCommands(), [self::COMMAND_ARTISAN, self::COMMAND_RELOAD_QUEUE]);
    }

    /**
     * @inheritdoc
     */
    public function executeSubCommand(string $subcommand, array $args = []): int
    {
        switch ($subcommand) {
            case self::COMMAND_ARTISAN:
                return parent::executeSubCommand("./artisan", $args);
            case self::COMMAND_RELOAD_QUEUE:
                return parent::executeSubCommand("supervisorctl", ["restart", "horizon"]);
            default:
                return parent::executeSubCommand($subcommand, $args);
        }
    }
}
