<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Smarty\Smarty;

/**
 * Vanilla implementation of Smarty security policy.
 */
class SmartySecurityVanilla extends \Smarty\Security
{
    /**
     * SmartySecurityVanilla constructor.
     *
     * @param Smarty $smarty
     */
    public function __construct(Smarty $smarty)
    {
        parent::__construct($smarty);
        $smarty->muteUndefinedOrNullWarnings();
        $this->php_handling = [];
        $this->disabled_tags = ["include_php", "insert"];
        $this->static_classes = null;
        $this->disabled_special_smarty_vars[] = "template_object";
    }
}
