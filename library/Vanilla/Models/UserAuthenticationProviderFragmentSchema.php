<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;

/**
 * Class UserAuthenticationProviderFragmentSchema
 */
class UserAuthenticationProviderFragmentSchema extends Schema {

    /**
     * UserAuthenticationProviderSchema constructor.
     */
    public function __construct() {
        parent::__construct($this->parseInternal([
            "authenticatorID:i",
            "name:s|n",
            "type:s",
            "clientID:s",
            "default:b",
            "active:b",
            "visible:b",
            "urls" => [
                "type" => "object",
                "properties" => [
                    "signInUrl:s",
                    "signOutUrl:s",
                    "authenticateUrl:s",
                    "registerUrl:s",
                    "passwordUrl:s",
                    "profileUrl:s",
                ],
                "allowNull" => true,
            ],
        ]));
    }
}
