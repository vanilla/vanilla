<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Docker\Service\AbstractService;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

class DockerContainerCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;

    const COMMAND_SSH = "ssh";
    const COMMAND_COMPOSER = "composer";

    public function __construct(private AbstractService $service)
    {
        parent::__construct();
    }

    /**
     * @return string[]
     */
    protected static function getSubCommands(): array
    {
        return [
            DockerCommand::COMMAND_UP,
            DockerCommand::COMMAND_START,
            DockerCommand::COMMAND_DOWN,
            DockerCommand::COMMAND_STOP,
            self::COMMAND_SSH,
            self::COMMAND_COMPOSER,
        ];
    }

    /**
     * Declare input arguments.
     */
    protected function configure(): void
    {
        parent::configure();
        $commands = implode(", ", static::getSubCommands()) . ", anything-else";

        $this->setName("docker:" . $this->service->descriptor->serviceID)
            ->setDescription("Work with the '{$this->service->descriptor->serviceID}' docker container.")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputArgument(
                        "sub-command",
                        Console\Input\InputArgument::REQUIRED,
                        "One of: $commands"
                    ),
                    new Console\Input\InputArgument(
                        "args",
                        Console\Input\InputArgument::IS_ARRAY,
                        "Arguments to pass to the command."
                    ),
                ])
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $input->getArgument("sub-command");
        return $this->executeSubCommand($command, $input->getArgument("args"));
    }

    /**
     * @param string $subcommand
     * @param array $args
     *
     * @return int
     */
    public function executeSubCommand(string $subcommand, array $args = []): int
    {
        switch ($subcommand) {
            case self::COMMAND_SSH:
                $this->service->ensureRunning();
                $sshProcess = $this->getSshProcess();
                $sshProcess->setTty(true);
                $sshProcess->setTimeout(0);
                $sshProcess->setIdleTimeout(0);
                return $sshProcess->run();
            case DockerCommand::COMMAND_UP:
            case DockerCommand::COMMAND_START:
                $dockerCommand = new DockerCommand();
                $dockerCommand->initServiceIDs([$this->service->descriptor->serviceID]);
                $dockerCommand->start();
                return self::SUCCESS;
            case DockerCommand::COMMAND_DOWN:
            case DockerCommand::COMMAND_STOP:
                $this->service->ensureRunning();
                $dockerCommand = new DockerCommand();
                $dockerCommand->initServiceIDs([$this->service->descriptor->serviceID]);
                $dockerCommand->stop();
                return self::SUCCESS;
            default:
                $this->service->ensureRunning();
                $process = new Process(
                    array_merge(
                        ["docker", "exec"],
                        ["-it", $this->service->descriptor->containerName],
                        [$subcommand],
                        $args
                    )
                );
                $process->setTty(true);
                $process->setTimeout(0);
                $process->setIdleTimeout(0);
                return $process->run();
        }
    }

    /**
     * Get an interactive ssh process using either bash or sh, depending on what's available in the container.
     *
     * @return Process
     */
    private function getSshProcess(): Process
    {
        $containerName = $this->service->descriptor->containerName;
        // Check if we have bash available
        $bashCheck = new Process(["docker", "exec", $containerName, "which", "bash"]);
        $bashCheck->run();
        if ($bashCheck->isSuccessful()) {
            return new Process(["docker", "exec", "-it", $containerName, "bash"]);
        } else {
            return new Process(["docker", "exec", "-it", $containerName, "sh"]);
        }
    }
}
