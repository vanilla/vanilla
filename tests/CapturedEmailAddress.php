<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

/**
 * Class CapturedEmailTo
 */
class CapturedEmailAddress {
    /**
     * @var string
     */
    public $email;

    /**
     * @var string|null
     */
    public $name;

    /**
     * CapturedEmailTo constructor.
     *
     * @param string $email
     * @param ?string $name
     */
    public function __construct(string $email, ?string $name) {
        $this->email = $email;
        $this->name = $name;
    }
}
