<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\JsonFilterTrait;

/**
 * A container for API attributes.
 */
class Attributes extends \ArrayObject implements \JsonSerializable {
    use JsonFilterTrait;

    /**
     * Attributes constructor.
     *
     * @param array|string|null $input The initial attributes.
     */
    public function __construct($input = null) {
        if (is_string($input)) {
            $input = dbdecode($input);
        } elseif (empty($input)) {
            $input = [];
        }

        parent::__construct($input, \ArrayObject::ARRAY_AS_PROPS);
    }


    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize() {
        $r = $this->getArrayCopy();
        if (empty($r)) {
            return (object)$r;
        }
        $r = $this->jsonFilter($r);
        return $r;
    }
}
