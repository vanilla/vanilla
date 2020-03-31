<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database;

use Vanilla\Models\PipelineModel;

/**
 * Simple class to represent a database operation.
 */
class Operation {

    /** Type identifier for delete operations. */
    const TYPE_DELETE = 'DELETE';

    /** Type identifier for insert operations. */
    const TYPE_INSERT = 'INSERT';

    /** Type identifier for select operations. */
    const TYPE_SELECT = 'SELECT';

    /** Type identifier for update operations. */
    const TYPE_UPDATE = 'UPDATE';

    const MODE_FORCE = 'force';

    const MODE_DEFAULT = 'default';

    /** @var PipelineModel Reference to the object performing this operation. */
    private $caller;

    /** @var array Options for the operation. */
    private $options = [];

    /** @var array Values to be set as part of the operation. */
    private $set = [];

    /** @var string Type of operation. Should be one of the TYPE_* SQL verb constants. */
    private $type = '';

    /** @var array Conditions to specify the scope of the operation. */
    private $where = [];

    private $mode = self::MODE_FORCE;

    /**
     * Get the reference to the object performing this operation.
     *
     * @return PipelineModel|null
     */
    public function getCaller() {
        return $this->caller;
    }

    /**
     * Get the options for the operation.
     *
     * @return array
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * Get the values to be set as part of the operation.
     *
     * @return array
     */
    public function getSet(): array {
        return $this->set;
    }

    /**
     * Get the type of operation to be performed.
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get the conditions to specify the scope of the operation.
     *
     * @return array
     */
    public function getWhere(): array {
        return $this->where;
    }

    /**
     * Set the reference to the object performing this operation.
     *
     * @param PipelineModel $caller
     */
    public function setCaller(PipelineModel $caller) {
        $this->caller = $caller;
    }

    /**
     * Set the options for the operation.
     *
     * @param array $options
     */
    public function setOptions(array $options) {
        $this->options = $options;
    }

    /**
     * Set the mode for the operation.
     *
     * @param string $mode
     */
    public function setMode(string $mode) {
        $this->mode = $mode;
    }

    /**
     * Get the mode for the operation.
     *
     * @return string
     */
    public function getMode(): string {
        return $this->mode;
    }

    /**
     * Assign the values to be set as part of the operation.
     *
     * @param array $set
     */
    public function setSet(array $set) {
        $this->set = $set;
    }

    /**
     * Set the type of operation to be performed.
     *
     * @param string $type Operation type. Should be one of the TYPE_* constants.
     */
    public function setType(string $type) {
        $this->type = $type;
    }

    /**
     * Set the conditions to specify the scope of the operation.
     *
     * @param array $where
     */
    public function setWhere(array $where) {
        $this->where = $where;
    }
}
