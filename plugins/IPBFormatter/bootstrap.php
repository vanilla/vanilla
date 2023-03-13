<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Formatting\FormatService;
use IPBFormatter\Formats\IPBFormat;
use IPBFormatter\Formatter;
use Garden\Container\Reference;

$container = Gdn::getContainer();

$container
    ->rule(FormatService::class)
    ->addCall("registerFormat", [IPBFormat::FORMAT_KEY, new Reference(IPBFormat::class)]);

$container
    ->rule(Formatter::class)
    ->addAlias("IPBFormatter")
    ->addAlias("ipbFormatter")
    ->setShared(true);
