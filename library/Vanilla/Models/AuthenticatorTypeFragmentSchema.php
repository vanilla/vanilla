<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;

/**
 * Class UserAuthenticationProviderFragmentSchema
 */
class AuthenticatorTypeFragmentSchema extends Schema
{
    /**
     * UserAuthenticationProviderFragmentSchema constructor.
     */
    public function __construct()
    {
        parent::__construct($this->parseInternal(["authenticatorType:s", "name:s", "description:s", "schema:o"]));
    }
}
