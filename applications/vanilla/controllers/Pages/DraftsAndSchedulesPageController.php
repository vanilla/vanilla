<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for /drafts page.
 */
class DraftsAndSchedulePageController extends PageDispatchController
{
    /**
     * Handle /drafts
     *
     * @return Data
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ServerException
     */
    public function index(): Data
    {
        return $this->useSimplePage("Drafts")
            ->permission("session.valid")
            ->setSeoTitle(t("Drafts"))
            ->blockRobots()
            ->render();
    }
}
