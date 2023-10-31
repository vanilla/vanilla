<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Vanilla\Scheduler\LongRunnerAction;

/** PSR event for a category scheduled for deletion */
class CategoryDeleteEvent
{
    /** @var int */
    private $categoryID;

    /** @var int|null */
    private $newCategoryID;

    /** @var LongRunnerAction[] */
    private $actions = [];

    /**
     * D.I.
     *
     * @param int $categoryID
     * @param array $options
     */
    public function __construct(int $categoryID, array $options)
    {
        $this->categoryID = $categoryID;
        $this->newCategoryID = $options["newCategoryID"] ?? null;
    }

    /**
     * Get the event's category ID.
     *
     * @return int
     */
    public function getCategoryID(): int
    {
        return $this->categoryID;
    }

    /**
     * Get the new category ID if one has been specified.
     *
     * @return int|mixed|null
     */
    public function getNewCategoryID()
    {
        return $this->newCategoryID;
    }

    /**
     * Get the Longrunner actions associated with this event.
     *
     * @return LongRunnerAction[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Add a Longrunner action to the event.
     *
     * @param LongRunnerAction $action
     */
    public function addAction(LongRunnerAction $action): void
    {
        $this->actions[] = $action;
    }
}
