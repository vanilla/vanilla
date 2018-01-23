<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden;

/**
 * For classes that want to store meta data.
 *
 * Meta data is an array of free form information that a class may require, but is separate from its main information.
 * It is sort of like HTTP header information for classes and can be useful in conveying information about how the data
 * was called.
 */
trait MetaTrait {
    /**
     * @var array
     */
    private $meta;

    /**
     * Get a single item from the meta array.
     *
     * @param string $name The key to get from.
     * @param mixed $default The default value if no item at the key exists.
     * @return mixed Returns the meta value.
     */
    public function getMeta($name, $default = null) {
        return isset($this->meta[$name]) ? $this->meta[$name] : $default;
    }

    /**
     * Set a single item to the meta array.
     *
     * @param string $name The key to set.
     * @param mixed $value The new value.
     * @return $this
     */
    public function setMeta($name, $value) {
        $this->meta[$name] = $value;
        return $this;
    }

    /**
     * Add a sub-item to a meta array.
     *
     * This method can take two forms.
     *
     * 1. `$o->addMeta('name', $value)` assumes that the item at **'name'** is a numeric array and adds **$value** to the end.
     * 2. `$o->addMeta('name', 'key', $value)` adds **$value** to the array at  **'name'** and uses **'key'** as the key.
     *     This may result in an existing item being overwritten.
     *
     * @param string $name The name of the meta key.
     * @param mixed[] $value Either a single value or a key then a value to set.
     * @return $this
     */
    public function addMeta($name, ...$value) {
        if (isset($this->meta[$name]) && !is_array($this->meta[$name])) {
            $this->meta[$name] = [$this->meta[$name]];
        }

        if (count($value) === 1) {
            $this->meta[$name][] = $value[0];
            return $this;
        }

        $path = $value;
        $value = array_pop($path);
        if (!isset($this->meta[$name])) {
            $this->meta[$name] = [];
        }

        $selection = &$this->meta[$name];
        foreach ($path as $subSelector) {
            if (!is_array($selection)) {
                $selection = [$selection];
            }

            if (!isset($selection[$subSelector])) {
                $selection[$subSelector] = [];
            }
            $selection = &$selection[$subSelector];
        }
        $selection = $value;
        return $this;
    }

    /**
     * Get the entire meta array.
     *
     * @return array Returns the meta.
     */
    public function getMetaArray() {
        return $this->meta;
    }

    /**
     * Set the entire meta array.
     *
     * @param array $meta The new meta array.
     * @return $this
     */
    public function setMetaArray(array $meta) {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Merge another meta array with this one.
     *
     * @param array $meta The meta array to merge.
     * @return $this
     */
    public function mergeMetaArray(array $meta) {
        $this->meta = array_merge_recursive($this->meta, $meta);
        return $this;
    }
}
