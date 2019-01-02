<?php
/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\TokenSigningTrait;

class TokenTestingModel {

    use TokenSigningTrait;

    /**
     * TokenModel constructor.
     */
    public function __construct() {
        $this->setSecret('sss');
        $this->tokenIdentifier = 'nonce';
    }
}
