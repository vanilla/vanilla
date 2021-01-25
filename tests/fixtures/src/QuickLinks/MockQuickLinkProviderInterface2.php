<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\QuickLinks;

use Vanilla\Theme\VariableProviders\QuickLink;
use Vanilla\Theme\VariableProviders\QuickLinkProviderInterface;

/**
 * Class MockQuickLinkProviderInterface2
 *
 * @package VanillaTests\Fixtures\QuickLinks
 */
class MockQuickLinkProviderInterface2 implements QuickLinkProviderInterface {

    /**
     * Provide some quick links.
     *
     * @return QuickLink[]
     */
    public function provideQuickLinks(): array {
        return [
            new QuickLink(
                'Mock Quick Link 2',
                '/mockQuickLink2',
                4
            )
        ];
    }
}
