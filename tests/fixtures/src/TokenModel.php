<?php
/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\Fixtures;


use Vanilla\TokenSigningTrait;

/**
 * Class TokenModel
 *
 */
class TokenModel {

    use TokenSigningTrait;
    public $tokenIdentifier;
    public $secret;

    public function __construct() {
        $this->secret = $this->setSecret('sss');
        $this->tokenIdentifier = 'nonce';
    }
}
