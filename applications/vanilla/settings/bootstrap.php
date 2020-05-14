<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\Reference;

$dic = \Gdn::getContainer();
$dic->rule(\Vanilla\Navigation\BreadcrumbModel::class)
    ->addCall('addProvider', [new Reference(\Vanilla\Forum\Navigation\ForumBreadcrumbProvider::class)])
;
