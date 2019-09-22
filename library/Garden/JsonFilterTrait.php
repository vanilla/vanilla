<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;


trait JsonFilterTrait {
    /**
     * Prepare data for json_encode
     *
     * @param mixed $value
     * @return mixed
     */
    private function jsonFilter($value) {
        $fn = function (&$value, $key = '', $parentKey = '') use (&$fn) {
            if (is_array($value)) {
                array_walk($value, function (&$childValue, $childKey) use ($fn, $key) {
                    $fn($childValue, $childKey, $key);
                });
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTime::RFC3339);
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
     * Decode a packed IP address to its human-readable form.
     *
     * @param string $packedIP A string representing a packed IP address.
     * @return string|null A human-readable representation of the provided IP address.
     */
    private function ipDecode($packedIP) {
        if (filter_var($packedIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            // If it's already a valid IP address, don't bother unpacking it.
            $result = $packedIP;
        } elseif ($iP = @inet_ntop($packedIP)) {
            $result = $iP;
        } else {
            $result = null;
        }

        return $result;
    }
}
