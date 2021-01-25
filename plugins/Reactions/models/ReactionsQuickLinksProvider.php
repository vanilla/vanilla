<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Reactions\Models;

use Vanilla\Theme\VariableProviders\QuickLink;
use Vanilla\Theme\VariableProviders\QuickLinkProviderInterface;

/**
 * Provide quicklinks.
 */
class ReactionsQuickLinksProvider implements QuickLinkProviderInterface {
    /**
     * Provide some quick links.
     *
     * @return QuickLink[]
     */
    public function provideQuickLinks(): array {
        return [
            new QuickLink(
                t('Best Of'),
                '/bestof',
                null
            )
        ];
    }
}
