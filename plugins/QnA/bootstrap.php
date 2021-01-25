<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\QnA\Models\QnaQuickLinksProvider;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;

\Gdn::getContainer()
    ->rule(QuickLinksVariableProvider::class)
    ->addCall('addQuickLinkProvider', [new \Garden\Container\Reference(QnaQuickLinksProvider::class)]);
