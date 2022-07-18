<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Cloud;

use Vanilla\Cli\VanillaCli;
use Vanilla\Cli\Cloud\Commands;

/**
 * VanillaCli with cloud commands registered.
 */
class VanillaCloudCli extends VanillaCli
{
    /**
     * Register vanilla-cloud only commands.
     */
    protected function configureCli(): void
    {
        parent::configureCli();
        $this->addMethod(Commands\SyncOssCommand::class, "syncOss", [self::OPT_SETTERS => true]);
        $this->addMethod(Commands\CloneCommand::class, "clone", [self::OPT_SETTERS => true]);
    }
}
