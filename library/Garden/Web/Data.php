<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;


/**
 * Represents the data in a web response.
 */
class Data implements \JsonSerializable {
    private $data;

    private $meta;

    /**
     * Create a {@link Data} instance representing the data in a web response.
     *
     * @param mixed $data The main response data.
     * @param array|int $meta Either an array of meta information or an integer HTTP response status.
     */
    public function __construct($data, $meta = 200) {
        $this->data = $data;

        if (is_int($meta)) {
            $this->meta = ['status' => $meta];
        } else {
            $this->meta = $meta;
        }
    }

    /**
     * Get a single item from the data array.
     *
     * @param string $name The key to get from.
     * @param mixed $default The default value if no item at the key exists.
     * @return mixed Returns the data value.
     */
    public function getDataItem($name, $default = null) {
        if (!is_array($this->data) && !($this->data instanceof \ArrayAccess)) {
            throw new \Exception("Data is not an array.", 500);
        }
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    /**
     * Set a single item to the data array.
     *
     * @param string $name The key to set.
     * @param mixed $value The new value.
     * @return $this
     */
    public function setDataItem($name, $value) {
        if (!is_array($this->data) && !($this->data instanceof \ArrayAccess)) {
            throw new \Exception("Data is not an array.", 500);
        }
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Get the entire data payload.
     *
     * @return mixed Returns the data.
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set the entire data payload.
     *
     * @param mixed $data The new data array.
     * @return $this
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the HTTP status.
     *
     * @return int Returns the status.
     */
    public function getStatus() {
        return $this->getMeta('status', 200);
    }

    /**
     * Set the HTTP status.
     *
     * @param int $status The new status.
     * @return $this
     */
    public function setStatus($status) {
        return $this->setMeta('status', $status);
    }

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
     * Specify data which should be serialized to JSON.
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize() {
        $data = $this->getData();
        jsonFilter($data);
        return $data;
    }

    /**
     * Render the response to the output.
     */
    public function render() {
        http_response_code($this->getStatus());
        header('Content-Type: application/json; charset=utf-8', true);

        echo json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
