<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla;

/**
 * Class ArrayAccessTrait.
 * Implementation of the ArrayAccess functions.
 *
 * When using this object as an array its properties are referenced.
 */
trait ArrayAccessTrait {

    /**
     * Returns the source from which ArrayAccess will be based on.
     * @return array|object
     */
    protected abstract function getArrayAccessSource();

    /**
     * Whether an offset exists.
     *
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     *
     * The return value will be casted to boolean if non-boolean was returned.
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset) {
        $source = $this->getArrayAccessSource();
        return is_array($source) ? isset($source[$offset]) : isset($source->$offset);
    }

    /**
     * Retrieve a value at a given array offset.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset) {
        $source = $this->getArrayAccessSource();
        return is_array($source) ? $source[$offset] : $source->$offset;
    }

    /**
     * Set a value at a given array offset.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value) {
        $source = $this->getArrayAccessSource();
        if (is_array($source)) {
            $source[$offset] = $value;
        } else {
            $source->$offset = $value;
        }
    }

    /**
     * Unset an array offset.
     *
     * @param mixed $offset The offset to unset.
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset) {
        $source = $this->getArrayAccessSource();
        if (is_array($source)) {
            unset($source[$offset]);
        } else {
            unset($source->$offset);
        }
    }
}
