<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use ArrayIterator;
use Countable;
use Vanilla\ArrayAccessTrait;
use Vanilla\Models\UserFragmentSchema;

/**
 * Data structure object containing a User fragment data.
 */
class UserFragment implements \ArrayAccess, \JsonSerializable, \IteratorAggregate, Countable {

    use ArrayAccessTrait;

    /** @var array */
    private $data;

    /** @var string|null */
    private $email;

    /** @var string|null */
    private $insertIPAddress;

    /** @var string|null */
    private $lastIPAddress;

    /** Flag to include the User email in the Data fragment. */
    const INCLUDE_EMAIL = 0x01;

    /** Flag to include the User email in the Data fragment. */
    const INCLUDE_IP = 0x02;

    /**
     * DI.
     *
     * @param array $data
     */
    public function __construct(array $data) {
        $this->email = $data['email'] ?? $data['Email'] ?? null;
        $this->insertIPAddress = $data['insertIPAddress'] ?? $data['InsertIPAddress'] ?? null;
        $this->lastIPAddress = $data['lastIPAddress'] ?? $data['LastIPAddress'] ?? null;
        $this->data = UserFragmentSchema::normalizeUserFragment($data);
    }

    /**
     * @return array
     */
    protected function getArrayAccessSource(): array {
        return $this->data;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        return $this->data;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->data);
    }

    /**
     * Return the user fragment including the specified PII.
     *
     * @param int $flags
     * @return array
     */
    public function serializeWithSensitiveData(int $flags): array {
        $sensitive = [];

        if (self::INCLUDE_EMAIL === $flags) {
            $sensitive['email'] = $this->email;
        }

        if (self::INCLUDE_IP === $flags) {
            $sensitive['insertIPAddress'] = $this->insertIPAddress;
            $sensitive['lastIPAddress'] = $this->insertIPAddress;
        }

        return array_merge($this->data, $sensitive);
    }

    /**
     * D.I.
     *
     * @return array|int
     */
    public function count() {
        return $this->data;
    }

    /**
     * Looks for UserFragment in an array and replace it with it's array value.
     *
     * @param array $rows
     * @return array
     */
    public static function userFragmentToArray(array $rows): array {
        foreach ($rows as $key => $value) {
            if ($value instanceof UserFragment) {
                $rows[$key] = $value->jsonSerialize();
            } else if (is_array($value)) {
                $rows[$key] = self::userFragmentToArray($value);
            }
        }
        return $rows;
    }
}
