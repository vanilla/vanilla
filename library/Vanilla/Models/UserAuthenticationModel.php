<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

/**
 * Model for managing UserAuthentication records.
 */
class UserAuthenticationModel extends Model
{
    /**
     * Initial model setup.
     */
    public function __construct()
    {
        parent::__construct("UserAuthentication");
    }
}
