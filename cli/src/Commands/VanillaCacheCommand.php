<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\AddonManager;
use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Setup\ComposerHelper;
use Symfony\Component\Console;

/**
 * Clear Caches command.
 */
class VanillaCacheCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName("clear-caches");
        $this->setDescription("Clear vanilla local file caches.");
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger()->info("Clearing PHP file caches.");
        ComposerHelper::clearPhpCache();
        ComposerHelper::clearTwigCache();
        ComposerHelper::clearJsDepsCache();

        $this->logger()->info("Rebuilding Addon Cache");
        $addonManager = new AddonManager(AddonManager::getDefaultScanDirectories(), PATH_CACHE);
        $addonManager->ensureMultiCache();

        $this->logger()->info("Clearing Memcached");
        DockerUtils::containerCommand(
            DockerCommand::VNLA_DOCKER_CWD,
            "memcached",
            "/",
            "echo 'flush_all' | nc localhost 11211"
        );

        $this->logger()->success("Caches cleared.");

        return 0;
    }
}
