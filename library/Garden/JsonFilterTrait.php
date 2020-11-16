<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

/**
 * Filters output before being JSON-encoded.
 */
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
                $isIPField = strlen($key) >= 9 && (
                    substr_compare($key, 'IPAddress', -9, 9, true) === 0 ||
                    strcasecmp('AllIPAddresses', $key) === 0
                );
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
