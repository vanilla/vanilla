<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database;

use Garden\MetaTrait;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;

/**
 * Simple class to represent a database operation.
 */
class Operation {

    use MetaTrait;

    /** Type identifier for delete operations. */
    const TYPE_DELETE = 'DELETE';

    /** Type identifier for insert operations. */
    const TYPE_INSERT = 'INSERT';

    /** Type identifier for select operations. */
    const TYPE_SELECT = 'SELECT';

    /** Type identifier for update operations. */
    const TYPE_UPDATE = 'UPDATE';

    const MODE_DEFAULT = 'default';

    const MODE_IMPORT = 'import';

    /** @var Model Reference to the object performing this operation. */
    private $caller;

    /** @var array Options for the operation. */
    private $options = [];

    /** @var array Values to be set as part of the operation. */
    private $set = [];

    /** @var string Type of operation. Should be one of the TYPE_* SQL verb constants. */
    private $type = '';

    /** @var array Conditions to specify the scope of the operation. */
    private $where = [];

    /**
     * Get the reference to the object performing this operation.
     *
     * @return Model|null
     */
    public function getCaller() {
        return $this->caller;
    }

    /**
     * Get an individual option item.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public function getOptionItem(string $key, $default = null) {
        return $this->options[$key] ?? $default;
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
     * Get the value of a particular set field.
     *
     * @param string $field The field to get.
     * @return mixed|null Returns the value or **null** if the field isn't being set.
     */
    public function getSetItem(string $field) {
        return $this->set[$field] ?? null;
    }

    /**
     * Set the value of a particular set field.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function setSetItem(string $field, $value): self {
        $this->set[$field] = $value;
        return $this;
    }

    /**
     * Determine whether or not a field is being set.
     *
     * @param string $field
     * @return bool
     */
    public function hasSetItem(string $field): bool {
        return array_key_exists($field, $this->set);
    }

    /**
     * Unset a set field so that it won't perform the operation.
     *
     * @param string $field The field to unset.
     * @return $this
     */
    public function removeSetItem(string $field): self {
        unset($this->set[$field]);
        return $this;
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
     * Get a single where item.
     *
     * @param string $field The field to look up.
     * @return mixed|null Returns the where value or **null** if there is no where expression for the field.
     */
    public function getWhereItem(string $field) {
        return $this->where[$field] ?? null;
    }

    /**
     * Determine whether or not the where has all of the given fields.
     *
     * @param string[] $fields The names of the fields to look up.
     * @return bool
     */
    public function hasAllWhereItems(string ...$fields): bool {
        foreach ($fields as $field) {
            if (!$this->hasWhereItem($field)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Pluck the where items out of the where clause.
     *
     * @param string[] $fields The names of the fields to pluck.
     * @return array
     */
    public function pluckWhereItems(string ...$fields): array {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $this->getWhereItem($field);
        }
        return $result;
    }

    /**
     * Set a single where item.
     *
     * @param string $field The field to filter on.
     * @param mixed $value The filter value.
     * @return $this
     */
    public function setWhereItem(string $field, $value): self {
        $this->where[$field] = $value;
        return $this;
    }

    /**
     * Determine whether or not the where clause is filtering on a field.
     *
     * @param string $field The field to look up.
     * @return bool Returns **true** if the where is filtering on the field or **false** otherwise.
     */
    public function hasWhereItem(string $field): bool {
        return array_key_exists($field, $this->where);
    }

    /**
     * Remove a where filter.
     *
     * This method does nothing if the field wasn't in the where clause in the first place.
     *
     * @param string $field The field to remove.
     * @return $this
     */
    public function removeWhereItem(string $field): self {
        unset($this->where[$field]);
        return $this;
    }

    /**
     * Set the reference to the object performing this operation.
     *
     * @param Model $caller
     */
    public function setCaller(Model $caller) {
        $this->caller = $caller;
    }

    /**
     * Set an individual option item.
     *
     * @param string $key
     * @param mixed $item
     */
    public function setOptionItem(string $key, $item): void {
        $this->options[$key] = $item;
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
        $this->options[Model::OPT_MODE] = $mode;
    }

    /**
     * Get the mode for the operation.
     *
     * @return string
     */
    public function getMode(): string {
        return $this->options[Model::OPT_MODE] ?? self::MODE_DEFAULT;
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
