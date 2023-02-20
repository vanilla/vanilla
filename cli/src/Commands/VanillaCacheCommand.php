<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Setup\ComposerHelper;
use Symfony\Component\Console;

/**
 * Clear Caches command.
 */
class VanillaCacheCommand extends Console\Command\Command
{
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
        ComposerHelper::clearPhpCache();
        ComposerHelper::clearTwigCache();
        return 0;
    }
}
