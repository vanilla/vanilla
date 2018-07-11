<?php
/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
