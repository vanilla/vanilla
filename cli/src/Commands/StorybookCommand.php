<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Utils\InstallDataTrait;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\ShellUtils;
use Webmozart\PathUtil\Path;

class StorybookCommand extends Console\Command\Command
{
    public const VALID_MODES = ["dev", "build", "generate"];

    use ScriptLoggerTrait;
    use InstallDataTrait;

    /** @var string[] */
    private array $sections = [];

    private string $mode = "dev";

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("storybook")
            ->setDescription("Run the vanilla storybook")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputArgument(
                        "mode",
                        Console\Input\InputArgument::OPTIONAL,
                        "The build mode. Either 'dev', 'build', 'generate'. Defaults to dev which runs a dev server."
                    ),
                ])
            );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $mode = $input->getArgument("mode") ?: "dev";
        if (!in_array($mode, self::VALID_MODES)) {
            throw new Console\Exception\InvalidArgumentException(
                "Invalid mode. Must be one of: " . implode(", ", self::VALID_MODES)
            );
        }

        $this->mode = $mode;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($this->mode) {
            case "dev":
                $command = "yarn run storybook";
                break;
            case "build":
                $command = "yarn run storybook:build";
                break;
            case "generate":
                $command = "./vendor/bin/phpunit -c phpunit.xml --testsuite Storybook --testdox";
                break;
            default:
                throw new Console\Exception\InvalidArgumentException("Invalid mode '{$this->mode}'.");
        }
        $this->logger()->info($command);
        return self::SUCCESS;
    }
}
