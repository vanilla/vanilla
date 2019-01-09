<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

class Tuple {
    public $a;
    public $b;

    public function __construct($a = null, $b = null) {
        $this->a = $a;
        $this->b = $b;
    }

    public function getA() {
        return $this->a;
    }

    public function setA($value) {
        $this->a = $value;
        return $this;
    }

    public function getB() {
        return $this->b;
    }

    public function setB($value) {
        $this->b = $value;
        return $this;
    }

    public static function create($a = 'a', $b = 'b') {
        return new Tuple($a, $b);
    }
}
