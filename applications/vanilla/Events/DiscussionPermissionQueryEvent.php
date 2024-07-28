<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Events;

/**
 * Event fired when we apply permission filters to a query affecting GDN_Discussion.
 */
class DiscussionPermissionQueryEvent
{
    /**
     * @param \Gdn_SQLDriver $sql
     * @param array $whereGroups
     */
    public function __construct(private \Gdn_SQLDriver $sql, private array $whereGroups)
    {
    }

    /**
     * Add an additional where clause to the query.
     *
     * @param array $where
     * @return void
     */
    public function addWhereGroup(array $where): void
    {
        $this->whereGroups[] = $where;
    }

    /**
     * @return \Gdn_SQLDriver
     */
    public function getSql(): \Gdn_SQLDriver
    {
        return $this->sql;
    }

    /**
     * @return array
     */
    public function getWhereGroups(): array
    {
        return $this->whereGroups;
    }

    /**
     * Apply the collected where's to the query.
     *
     * @return void
     */
    public function applyWheresToQuery(): void
    {
        $sql = $this->getSql();
        $whereGroups = $this->getWhereGroups();

        if (count($whereGroups) === 0) {
            // There's just the one.
            $sql->where(reset($whereGroups));
            return;
        }

        foreach ($whereGroups as $where) {
            $sql->orOp();
            $sql->beginWhereGroup();
            $sql->where($where);
            $sql->endWhereGroup();
        }
    }
}
