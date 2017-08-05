<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Fixtures;


class CountCallDecorator extends \AbstractDecorator {

    /** @var array */
    protected $callsCount = [];

    /**
     * CountCallDecorator constructor.
     *
     * @param mixed $object
     */
    public function __construct($object) {
        parent::__construct($object);
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args) {
        $return = parent::__call($method, $args);

        if (!isset($this->callsCount[$method])) {
            $this->callsCount[$method] = 1;
        } else {
            $this->callsCount[$method]++;
        }

        return $return;
    }

    /**
     * Get an array containing the list of called methods and the number of time they have been called.
     *
     * @return array
     */
    public function getCallsCount() {
        return $this->callsCount;
    }
}
