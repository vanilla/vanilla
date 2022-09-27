<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Models\SiteMetaExtra;

/**
 * Site meta extra implementation for easy use in tests.
 */
class MockSiteMetaExtra extends SiteMetaExtra
{
    /** @var array */
    private $value;

    /**
     * Constructor.
     *
     * @param array $value
     */
    public function __construct(array $value = [])
    {
        $this->value = $value;
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        return $this->value;
    }
}
