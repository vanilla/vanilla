<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Fixtures;


class BaseClassDecorator extends \AbstractDecorator {
    /**
     * BaseClassDecorator constructor.
     *
     * @param BaseClass $object
     */
    public function __construct($object) {
        parent::__construct($object);
    }

    /**
     * Return 'Decorated Foo Bar'
     *
     * @return string
     */
    public function foobar() {
        return 'Decorated '.$this->object->foobar();
    }
}
