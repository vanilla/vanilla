<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


class Attributes extends \ArrayObject implements \JsonSerializable {
    public function __construct($input = null) {
        if (is_string($input)) {
            $input = dbdecode($input);
        } elseif (empty($input)) {
            $input = [];
        }

        parent::__construct($input, \ArrayObject::ARRAY_AS_PROPS);
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize() {
        $r = (object)$this->getArrayCopy();
        return $r;
    }
}
