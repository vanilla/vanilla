<?php

/**
 * Vanilla implementation of Smarty security policy.
 */
class SmartySecurityVanilla extends Smarty_Security {

    /**
     * Check if PHP function is trusted.
     *
     * @param  string $function_name
     * @param  object $compiler compiler object
     *
     * @return boolean                 true if function is trusted
     * @throws SmartyCompilerException if php function is not trusted
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
     *
     * @return array
     * @throws Gdn_UserException if $php_functions is not an array.
     */
    public function setPhpFunctions($php_functions) {
        if (!is_array($php_functions)) {
            throw new Gdn_UserException('$php_functions must be an array.');
        }

        $this->php_functions = array_map('strtolower', $php_functions);
    }
}
