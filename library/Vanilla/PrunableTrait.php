<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Utility methods for models that want to implement pruning.
 *
 */
trait PrunableTrait
{
    /**
     * @var string The amount of time to delete records after.
     */
    private $pruneAfter = "30 days";

    /**
     * @var string
     */
    private $pruneField = "DateInserted";

    /**
     * @var int The number of rows to delete when pruning.
     */
    private $pruneLimit = 10;

    /** @var array Extra where clauses for pruning. */
    private $pruneWhere = [];

    /**
     * Get the delete after time.
     *
     * @return string Returns a string compatible with {@link strtotime()}.
     */
    public function getPruneAfter()
    {
        return $this->pruneAfter;
    }

    /**
     * Set the prune after date.
     *
     * @param string $pruneAfter A string compatible with {@link strtotime()}.
     * @return $this
     */
    public function setPruneAfter($pruneAfter)
    {
        if ($pruneAfter) {
            // Make sure the string can be converted into a date.
            $now = CurrentTimeStamp::get();
            $testTime = strtotime($pruneAfter, $now);
            if ($testTime === false) {
                throw new \InvalidArgumentException('Invalid timespan value for "prune after".', 400);
            }
        }

        $this->pruneAfter = $pruneAfter;
        return $this;
    }

    /**
     * Get the pruneField.
     *
     * @return string Returns the pruneField.
     */
    public function getPruneField()
    {
        return $this->pruneField;
    }

    /**
     * Set the pruneField.
     *
     * @param string $pruneField The name of the new prune field.
     * @return $this
     */
    public function setPruneField($pruneField)
    {
        $this->pruneField = $pruneField;
        return $this;
    }

    /**
     * Prune old rows.
     *
     * @param int|null $limit Then number of rows to delete or **null** to use the default prune limit.
     */
    public function prune($limit = null)
    {
        $date = $this->getPruneDate();
        if ($date === null) {
            return;
        }

        $options = [];
        if ($limit === null) {
            $options["limit"] = $this->getPruneLimit();
        } elseif ($limit !== 0) {
            $options["limit"] = $limit;
        }

        $this->delete(
            array_merge($this->pruneWhere, [
                $this->getPruneField() . " <" => $date->format("Y-m-d H:i:s"),
            ]),
            $options
        );
    }

    /**
     * Perform the actual database delete.
     *
     * @param array $where The where clause.
     * @param array $options Options for the delete.
     * @return mixed
     */
    abstract public function delete($where = [], $options = []);

    /**
     * Get the exact timestamp to prune.
     *
     * @return \DateTimeInterface|null Returns the date that we should prune after.
     */
    public function getPruneDate()
    {
        if (!$this->pruneAfter) {
            return null;
        } else {
            $now = CurrentTimeStamp::getDateTime();
            $test = CurrentTimeStamp::getDateTime()->modify($this->pruneAfter);

            $interval = $test->diff($now);

            if ($interval->invert === 1) {
                return $now->add($interval);
            } else {
                return $test;
            }
        }
    }

    /**
     * Get the number of rows deleted with each prune.
     *
     * @return int Returns the pruneLimit.
     */
    public function getPruneLimit()
    {
        return $this->pruneLimit;
    }

    /**
     * Set the number of rows deleted with each prune.
     *
     * @param int $pruneLimit The new prune limit.
     * @return $this
     */
    public function setPruneLimit($pruneLimit)
    {
        $this->pruneLimit = $pruneLimit;
        return $this;
    }

    /**
     * Add extra criteria for the items being pruned. Don't forget to index!
     *
     * @param array $pruneWhere
     */
    public function setPruneWhere(array $pruneWhere): void
    {
        $this->pruneWhere = $pruneWhere;
    }
}
