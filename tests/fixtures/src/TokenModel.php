<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;


use Vanilla\TokenSigningTrait;

class TokenModel {

    use TokenSigningTrait;
    public $token = "va.7yE_QzDEbXDqcFkjohNor9ZnB9qgnv8a.Kbw_Ww.JbenMOl";
    public $tokenIdentifier;
    public $secret;

    public function __construct($secret = '') {
        $this->secret = 'sss';
    }

}
