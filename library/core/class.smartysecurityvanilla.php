<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Vanilla implementation of Smarty security policy.
 */
class SmartySecurityVanilla extends Smarty_Security {

    /**
     * SmartySecurityVanilla constructor.
     *
     * @param Smarty $smarty
     */
    public function __construct($smarty) {
        parent::__construct($smarty);
        $this->php_handling = Smarty::PHP_REMOVE;
        $this->disabled_tags = ['include_php', 'insert'];
        $this->static_classes = null;
        $this->disabled_special_smarty_vars[] = 'template_object';
    }

    /**
     * Check if PHP function is trusted.
     *
     * @param  string $function_name
     * @param  object $compiler compiler object
     *
     * @return boolean Returns **true** if function is trusted.
     * @throws SmartyCompilerException If php function is not trusted.
     */
    public function isTrustedPhpFunction($function_name, $compiler) {
        if (isset($this->php_functions)) {
            if (empty($this->php_functions) || in_array(strtolower($function_name), $this->php_functions)) {
                return true;
            }
        }

        $compiler->trigger_template_error("PHP function '{$function_name}' not allowed by security setting");

        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Set allowed PHP functions.  Normalize casing for comparison.
     *
     * @param array $php_functions PHP functions to allow.
     * @throws Gdn_UserException If $php_functions is not an array.
     */
    public function setPhpFunctions($php_functions) {
        if (!is_array($php_functions)) {
            throw new Gdn_UserException('$php_functions must be an array.');
        }

        $this->php_functions = array_map('strtolower', $php_functions);
    }
}
