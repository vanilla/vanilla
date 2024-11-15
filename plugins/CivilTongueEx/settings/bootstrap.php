<?php
/**
 * @author Isis Graziatto<isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use CivilTongueEx\Library\ContentFilter;
use Vanilla\Utility\ContainerUtils;
use Garden\Container\Reference;
use Vanilla\Formatting\BaseFormat;
use CivilTongueEx\Library\Processor\CivilTongueProcessor;

$container = \Gdn::getContainer();

$container
    ->rule(ContentFilter::class)
    ->setClass(ContentFilter::class)
    ->addCall("setReplacement", [ContainerUtils::config("Plugins.CivilTongue.Replacement")])
    ->addCall("setWords", [ContainerUtils::config("Plugins.CivilTongue.Words")])

    ->rule(BaseFormat::class)
    ->addCall("addSanitizeProcessor", [new Reference(CivilTongueProcessor::class)]);
