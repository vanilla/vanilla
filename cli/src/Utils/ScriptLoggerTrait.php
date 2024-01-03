<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Utils;

trait ScriptLoggerTrait
{
    public function logger(): SimpleScriptLogger
    {
        /** @var SimpleScriptLogger $logger */
        static $logger = null;
        if ($logger === null) {
            $logger = new SimpleScriptLogger();
        }
        return $logger;
    }
}
