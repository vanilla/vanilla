<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Addons\Participated\ParticipatedQuickLinksProvider;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;

\Gdn::getContainer()
    ->rule(QuickLinksVariableProvider::class)
    ->addCall('addQuickLinkProvider', [new \Garden\Container\Reference(ParticipatedQuickLinksProvider::class)]);
