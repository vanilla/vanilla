<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Pages;

use Garden\Web\Exception\ServerException;
use Vanilla\Dashboard\Pages\LegacyDashboardPage;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests coverage for the LegacyDashboardPage.
 */
class LegacyDashboardPageTest extends AbstractAPIv2Test
{
    /**
     * Test initializing the page with no container set.
     *
     * @throws ServerException If controller is not set.
     */
    public function testPageInitNoController()
    {
        $page = self::container()->get(LegacyDashboardPage::class);
        $this->expectException(ServerException::class);
        $page->initialize();
    }
}
