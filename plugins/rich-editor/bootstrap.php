<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Models\SiteMeta;

\Gdn::getContainer()
    ->rule(SiteMeta::class)
    ->addCall("addExtra", [new \Garden\Container\Reference(RichEditorSiteMetaExtra::class)]);
