<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to Publish base docker images.
 */
class PlaceholderCommand extends Console\Command\Command
{
    /**
     * @param Console\Input\InputDefinition $definition
     */
    public function __construct(string $name, string $description, Console\Input\InputDefinition $definition)
    {
        parent::__construct($name);
        $this->setName($name);
        $this->setDescription($description);
        $this->setDefinition($definition);
    }
}
