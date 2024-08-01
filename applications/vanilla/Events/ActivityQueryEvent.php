<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Gdn_SQLDriver;

/**
 * Event for handling activity query checks.
 */
class ActivityQueryEvent
{
    protected Gdn_SQLDriver $activitySQL;

    protected string $tableAlias;
    /**
     * Constructor.
     *
     * @param Gdn_SQLDriver $activitySQL
     * @param string $tableAlias
     */
    public function __construct(\Gdn_SQLDriver &$activitySQL, $tableAlias = "a")
    {
        $this->tableAlias = $tableAlias;
        $this->activitySQL = &$activitySQL;
    }

    /**
     * Get the ActivitySQL.
     *
     * @return Gdn_SQLDriver
     */
    public function &getActivitySQL(): Gdn_SQLDriver
    {
        return $this->activitySQL;
    }

    /**
     * Get the table alias.
     *
     * @return string
     */
    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }
}
