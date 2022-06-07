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
class UserFragment implements \ArrayAccess, \JsonSerializable, \IteratorAggregate, Countable
{
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
    public function __construct(array $data)
    {
        $this->email = $data["email"] ?? ($data["Email"] ?? null);
        $this->insertIPAddress = $data["insertIPAddress"] ?? ($data["InsertIPAddress"] ?? null);
        $this->lastIPAddress = $data["lastIPAddress"] ?? ($data["LastIPAddress"] ?? null);
        $this->data = UserFragmentSchema::normalizeUserFragment($data);
    }

    /**
     * @return array
     */
    protected function &getArrayAccessSource(): array
    {
        return $this->data;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Return the user fragment including the specified PII.
     *
     * @param int $flags
     * @return array
     */
    public function serializeWithSensitiveData(int $flags): array
    {
        $sensitive = [];

        if (self::INCLUDE_EMAIL === $flags) {
            $sensitive["email"] = $this->email;
        }

        if (self::INCLUDE_IP === $flags) {
            $sensitive["insertIPAddress"] = $this->insertIPAddress;
            $sensitive["lastIPAddress"] = $this->insertIPAddress;
        }

        return array_merge($this->data, $sensitive);
    }

    /**
     * Apply some extra data into our fragment.
     *
     * @param array $data
     */
    public function addExtraData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Implement the \Countable interface.
     */
    public function count()
    {
        return count($this->data);
    }
}
