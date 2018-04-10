<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;
use Traversable;

use Garden\MetaTrait;
use DateTime;
use DateTimeInterface;

/**
 * Represents the data in a web response.
 */
class Data implements \JsonSerializable, \ArrayAccess, \Countable, \IteratorAggregate  {
    use MetaTrait;

    private $data;

    /**
     * Create a {@link Data} instance representing the data in a web response.
     *
     * @param mixed $data The main response data.
     * @param array|int $meta Either an array of meta information or an integer HTTP response status.
     */
    public function __construct($data = [], $meta = []) {
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
     * Add another data object as a sub array of this data.
     *
     * @param array|Data $data The data to add.
     * @param string $key The key to add the data to.
     * @param bool $mergeMeta Whether or not to merge the meta array.
     * @return $this
     */
    public function addData($data, $key, $mergeMeta = false) {
        if (is_array($data)) {
            $this->data[$key] = $data;
        } else {
            $this->data[$key] = $data->getData();
            if ($mergeMeta) {
                $this->mergeMetaArray($data->getMetaArray());
            }
        }
        return $this;
    }

    /**
     * Get the HTTP status.
     *
     * @return int Returns the status.
     */
    public function getStatus() {
        $status = $this->getMeta('status', null);
        if ($status === null) {
            $status = $this->data === null ? 204 : 200;
        } elseif ($status < 100 || $status > 527) {
            $status = 500;
        }
        return $status;
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
     * Specify data which should be serialized to JSON.
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize() {
        $data = $this->getData();
        $data = $this->jsonFilter($data);
        return $data;
    }

    /**
     * Get a header value.
     *
     * @param string $name The name of the header.
     * @param mixed $default The default value if the header does not exist.
     * @return mixed Returns the header value or {@link $default}.
     */
    public function getHeader($name, $default = null) {
        return $this->getMeta($this->headerKey($name), $default);
    }

    /**
     * Set a header value.
     *
     * @param string $name The name of the header.
     * @param mixed $value The header value.
     * @return $this
     */
    public function setHeader($name, $value) {
        $this->setMeta($this->headerKey($name), $value);
        return $this;
    }

    /**
     * Determine if a header exists.
     *
     * @param string $name The name of the header to check.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader($name) {
        return isset($this->meta[$this->headerKey($name)]);
    }

    /**
     * Get all of the headers.
     *
     * @return array Returns the headers as an array.
     */
    public function getHeaders() {
        $result = [];

        foreach ($this->meta as $key => $value) {
            if ($key === 'CONTENT_TYPE') {
                $result['Content-Type'] = $value;
            } elseif (substr_compare($key, 'HTTP_', 0, 5, true) === 0) {
                $headerKey = $this->headerName(substr($key, 5));

                $result[$headerKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Normalize a header name into a header key.
     *
     * @param string $name The name of the header.
     * @return string Returns a string in the form **HTTP_***.
     */
    private function headerKey($name) {
        $key = strtoupper(str_replace('-', '_', $name));
        if ($key !== 'CONTENT_TYPE') {
            $key = 'HTTP_'.$key;
        }
        return $key;
    }

    /**
     * Normalize a header key into a header name.
     *
     * @param string $name The header name to normalize.
     * @return string Returns a string in the form "Header-Name".
     */
    private function headerName($name) {
        static $special = ['Md5' => 'MD5', 'Dnt' => 'DNT', 'Etag' => 'ETag', 'P3p' => 'P3P', 'Tsv' => 'TSV', 'Www' => 'WWW'];

        if (strpos($name, '-') !== false) {
            return $name;
        } else {
            $parts = explode('_', $name);

            $result = implode('-', array_map(function ($part) use ($special) {
                $r = ucfirst(strtolower($part));
                return isset($special[$r]) ? $special[$r] : $r;
            }, $parts));

            return $result;
        }
    }

    /**
     * Render the response to the output.
     */
    public function render() {
        http_response_code($this->getStatus());

        if (!headers_sent()) {
            if (!$this->hasHeader('Content-Type')) {
                header('Content-Type: application/json; charset=utf-8', true);
            }
            foreach ($this->getHeaders() as $name => $value) {
                foreach ((array)$value as $line) {
                    header("$name: $line");
                }
            }
        }

        if (is_string($this->data) || $this->data === null) {
            echo $this->data;
        } else {
            echo json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }

    /**
     * Decode a packed IP address to its human-readable form.
     *
     * @param string $packedIP A string representing a packed IP address.
     * @return string|null A human-readable representation of the provided IP address.
     */
    private function ipDecode($packedIP) {
        if (filter_var($packedIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6)) {
            // If it's already a valid IP address, don't bother unpacking it.
            $result = $packedIP;
        } elseif ($iP = @inet_ntop($packedIP)) {
            $result = $iP;
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Prepare data for json_encode
     *
     * @param mixed $value
     * @return mixed
     */
    private function jsonFilter($value) {
        $fn = function (&$value, $key = '', $parentKey = '') use (&$fn) {
            if (is_array($value)) {
                array_walk($value, function(&$childValue, $childKey) use ($fn, $key) {
                    $fn($childValue, $childKey, $key);
                });
            } elseif ($value instanceof DateTimeInterface) {
                $value = $value->format(DateTime::RFC3339);
            } elseif (is_string($value)) {
                // Only attempt to unpack as an IP address if this field or its parent matches the IP field naming scheme.
                $isIPField = ($this->stringEndsWith($key, 'IPAddress', true) || $this->stringEndsWith($parentKey, 'IPAddresses', true));
                if ($isIPField && ($ip = $this->ipDecode($value)) !== null) {
                    $value = $ip;
                }
            }
        };

        if (is_array($value)) {
            array_walk($value, $fn);
        } else {
            $fn($value);
        }

        return $value;
    }

    /**
     * Whether a offset exists.
     *
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     * The return value will be casted to boolean if non-boolean was returned.
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset) {
        return $this->getDataItem($offset);
    }

    /**
     * Offset to set.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value) {
        $this->setDataItem($offset, $value);
    }

    /**
     * Offset to unset.
     *
     * @param mixed $offset The offset to unset.
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    /**
     * Checks whether or not string A ends with string B.
     *
     * @param string $haystack The main string to check.
     * @param string $needle The substring to check against.
     * @param bool $caseInsensitive Whether or not the comparison should be case insensitive.
     * @param bool $trim Whether or not to trim $B off of $A if it is found.
     * @return bool|string Returns true/false unless $trim is true.
     */
    private function stringEndsWith($haystack, $needle, $caseInsensitive = false, $trim = false) {
        if (strlen($haystack) < strlen($needle)) {
            return $trim ? $haystack : false;
        } elseif (strlen($needle) == 0) {
            if ($trim) {
                return $haystack;
            }
            return true;
        } else {
            $result = substr_compare($haystack, $needle, -strlen($needle), strlen($needle), $caseInsensitive) == 0;
            if ($trim) {
                $result = $result ? substr($haystack, 0, -strlen($needle)) : $haystack;
            }
            return $result;
        }
    }

    /**
     * Count elements of an object.
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count() {
        return count($this->data);
    }

    /**
     * Retrieve an external iterator.
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>.
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     */
    public function getIterator() {
        return new \ArrayIterator($this->data);
    }
}
