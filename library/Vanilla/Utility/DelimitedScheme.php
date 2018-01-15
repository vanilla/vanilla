<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Utility;


class DelimitedScheme extends NameScheme {
    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var NameScheme
     */
    private $scheme;

    public function __construct($delimiter, NameScheme $scheme) {
        $this->delimiter = $delimiter;
        $this->scheme = $scheme;
    }

    /**
     * Convert a name into this name spec.
     *
     * @param string $name The name to convert to this scheme.
     * @return string Returns the new name as a string.
     */
    public function convert($name) {
        $parts = explode($this->delimiter, $name);

        $converted = [];
        foreach ($parts as $part) {
            $converted[] = $this->scheme->convert($part);
        }

        $result = implode($this->delimiter, $converted);
        return $result;
    }

    /**
     * Test that a name is valid for this scheme.
     *
     * @param string $name The name to test.
     * @return bool Returns **true** if the name is valid for this spec or **false** otherwise.
     */
    public function valid($name) {
        $parts = explode($this->delimiter, $name);
        foreach ($parts as $part) {
            if (!$this->scheme->valid($part)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the scheme.
     *
     * @return NameScheme Returns the scheme.
     */
    public function getScheme() {
        return $this->scheme;
    }

    /**
     * Set the scheme.
     *
     * @param NameScheme $scheme
     * @return DelimitedScheme Returns `$this` for fluent calls.
     */
    public function setScheme($scheme) {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Get the delimiter.
     *
     * @return string Returns the delimiter.
     */
    public function getDelimiter() {
        return $this->delimiter;
    }

    /**
     * Set the delimiter.
     *
     * @param string $delimiter
     * @return DelimitedScheme Returns `$this` for fluent calls.
     */
    public function setDelimiter($delimiter) {
        $this->delimiter = $delimiter;
        return $this;
    }
}
