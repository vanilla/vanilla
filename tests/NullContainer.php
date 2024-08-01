<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

class NullContainer extends \Garden\Container\Container
{
    /**
     * NullContainer constructor.
     */
    public function __construct()
    {
    }

    /**
     * Make sure that we fail!
     * @inheritDoc
     */
    public function rule($id)
    {
        trigger_error(
            "NullContainer is being called which means that the bootstrapping process failed somewhere.",
            E_USER_ERROR
        );
        return $this;
    }

    /**
     * Make sure that we fail!
     */
    public function getArgs($id, array $args = [])
    {
        throw new \Exception(
            "NullContainer is being called which means that the bootstrapping process failed somewhere."
        );
    }
}
