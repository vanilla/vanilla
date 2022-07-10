<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * phpcs:ignoreFile
 */

namespace Vanilla;


use Smarty;

/**
 * Smarty Backward Compatibility Wrapper Class
 *
 * @package Smarty
 */
class SmartyBC extends Smarty
{
    /**
     * Smarty 2 BC
     *
     * @var string
     */
    public $_version = self::SMARTY_VERSION;

    /**
     * This is an array of directories where trusted php scripts reside.
     *
     * @var array
     * @deprecated
     */
    public $trusted_dir = array();

    /**
     * Initialize new SmartyBC object
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * wrapper for assign_by_ref
     *
     * @param string $tpl_var the template variable name
     * @param mixed  &$value  the referenced value to assign
     * @deprecated
     */
    public function assign_by_ref($tpl_var, &$value)
    {
        $this->assignByRef($tpl_var, $value);
    }

    /**
     * wrapper for append_by_ref
     *
     * @param string  $tpl_var the template variable name
     * @param mixed   &$value  the referenced value to append
     * @param boolean $merge   flag if array elements shall be merged
     * @deprecated
     */
    public function append_by_ref($tpl_var, &$value, $merge = false)
    {
        $this->appendByRef($tpl_var, $value, $merge);
    }

    /**
     * clear the given assigned template variable.
     *
     * @param string $tpl_var the template variable to clear
     * @deprecated
     */
    public function clear_assign($tpl_var)
    {
        $this->clearAssign($tpl_var);
    }

    /**
     * Registers custom function to be used in templates
     *
     * @param string $function      the name of the template function
     * @param string $function_impl the name of the PHP function to register
     * @param bool   $cacheable
     * @param mixed  $cache_attrs
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function register_function($function, $function_impl, $cacheable = true, $cache_attrs = null)
    {
        $this->registerPlugin('function', $function, $function_impl, $cacheable, $cache_attrs);
    }

    /**
     * Unregister custom function
     *
     * @param string $function name of template function
     * @deprecated
     */
    public function unregister_function($function)
    {
        $this->unregisterPlugin('function', $function);
    }

    /**
     * Registers object to be used in templates
     *
     * @param string  $object        name of template object
     * @param object  $object_impl   the referenced PHP object to register
     * @param array   $allowed       list of allowed methods (empty = all)
     * @param boolean $smarty_args   smarty argument format, else traditional
     * @param array   $block_methods list of methods that are block format
     *
     * @throws   \SmartyException
     * @internal param array $block_functs list of methods that are block format
     * @deprecated
     */
    public function register_object(
        $object,
        $object_impl,
        $allowed = array(),
        $smarty_args = true,
        $block_methods = array()
    ) {
        settype($allowed, 'array');
        settype($smarty_args, 'boolean');
        $this->registerObject($object, $object_impl, $allowed, $smarty_args, $block_methods);
    }

    /**
     * Unregister object
     *
     * @param string $object name of template object
     * @deprecated
     */
    public function unregister_object($object)
    {
        $this->unregisterObject($object);
    }

    /**
     * Registers block function to be used in templates
     *
     * @param string $block      name of template block
     * @param string $block_impl PHP function to register
     * @param bool   $cacheable
     * @param mixed  $cache_attrs
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function register_block($block, $block_impl, $cacheable = true, $cache_attrs = null)
    {
        $this->registerPlugin('block', $block, $block_impl, $cacheable, $cache_attrs);
    }

    /**
     * Unregister block function
     *
     * @param string $block name of template function
     * @deprecated
     */
    public function unregister_block($block)
    {
        $this->unregisterPlugin('block', $block);
    }

    /**
     * Registers compiler function
     *
     * @param string $function      name of template function
     * @param string $function_impl name of PHP function to register
     * @param bool   $cacheable
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function register_compiler_function($function, $function_impl, $cacheable = true)
    {
        $this->registerPlugin('compiler', $function, $function_impl, $cacheable);
    }

    /**
     * Unregister compiler function
     *
     * @param string $function name of template function
     * @deprecated
     */
    public function unregister_compiler_function($function)
    {
        $this->unregisterPlugin('compiler', $function);
    }

    /**
     * Registers modifier to be used in templates
     *
     * @param string $modifier      name of template modifier
     * @param string $modifier_impl name of PHP function to register
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function register_modifier($modifier, $modifier_impl)
    {
        $this->registerPlugin('modifier', $modifier, $modifier_impl);
    }

    /**
     * Unregister modifier
     *
     * @param string $modifier name of template modifier
     * @deprecated
     */
    public function unregister_modifier($modifier)
    {
        $this->unregisterPlugin('modifier', $modifier);
    }

    /**
     * Registers a resource to fetch a template
     *
     * @param string $type      name of resource
     * @param array  $functions array of functions to handle resource
     * @deprecated
     */
    public function register_resource($type, $functions)
    {
        $this->registerResource($type, $functions);
    }

    /**
     * Unregister a resource
     *
     * @param string $type name of resource
     * @deprecated
     */
    public function unregister_resource($type)
    {
        $this->unregisterResource($type);
    }

    /**
     * Registers a prefilter function to apply
     * to a template before compiling
     *
     * @param callable $function
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function register_prefilter($function)
    {
        $this->registerFilter('pre', $function);
    }

    /**
     * Unregister a prefilter function
     *
     * @param callable $function
     * @deprecated
     */
    public function unregister_prefilter($function)
    {
        $this->unregisterFilter('pre', $function);
    }

    /**
     * Registers a postfilter function to apply
     * to a compiled template after compilation
     *
     * @param callable $function
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function register_postfilter($function)
    {
        $this->registerFilter('post', $function);
    }

    /**
     * Unregister a postfilter function
     *
     * @param callable $function
     * @deprecated
     */
    public function unregister_postfilter($function)
    {
        $this->unregisterFilter('post', $function);
    }

    /**
     * Registers an output filter function to apply
     * to a template output
     *
     * @param callable $function
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function register_outputfilter($function)
    {
        $this->registerFilter('output', $function);
    }

    /**
     * Unregister an outputfilter function
     *
     * @param callable $function
     * @deprecated
     */
    public function unregister_outputfilter($function)
    {
        $this->unregisterFilter('output', $function);
    }

    /**
     * load a filter of specified type and name
     *
     * @param string $type filter type
     * @param string $name filter name
     *
     * @throws \SmartyException
     * @deprecated
     */
    public function load_filter($type, $name)
    {
        $this->loadFilter($type, $name);
    }

    /**
     * clear cached content for the given template and cache id
     *
     * @param string $tpl_file   name of template file
     * @param string $cache_id   name of cache_id
     * @param string $compile_id name of compile_id
     * @param string $exp_time   expiration time
     *
     * @return boolean
     * @deprecated
     */
    public function clear_cache($tpl_file = null, $cache_id = null, $compile_id = null, $exp_time = null)
    {
        return $this->clearCache($tpl_file, $cache_id, $compile_id, $exp_time);
    }

    /**
     * clear the entire contents of cache (all templates)
     *
     * @param string $exp_time expire time
     *
     * @return boolean
     * @deprecated
     */
    public function clear_all_cache($exp_time = null)
    {
        return $this->clearCache(null, null, null, $exp_time);
    }

    /**
     * test to see if valid cache exists for this template
     *
     * @param string $tpl_file name of template file
     * @param string $cache_id
     * @param string $compile_id
     *
     * @return bool
     * @throws \Exception
     * @throws \SmartyException
     * @deprecated
     */
    public function is_cached($tpl_file, $cache_id = null, $compile_id = null)
    {
        return $this->isCached($tpl_file, $cache_id, $compile_id);
    }

    /**
     * clear all the assigned template variables.
     * @deprecated
     */
    public function clear_all_assign()
    {
        $this->clearAllAssign();
    }

    /**
     * clears compiled version of specified template resource,
     * or all compiled template files if one is not specified.
     * This function is for advanced use only, not normally needed.
     *
     * @param string $tpl_file
     * @param string $compile_id
     * @param string $exp_time
     *
     * @return boolean results of {@link smarty_core_rm_auto()}
     * @deprecated
     */
    public function clear_compiled_tpl($tpl_file = null, $compile_id = null, $exp_time = null)
    {
        return $this->clearCompiledTemplate($tpl_file, $compile_id, $exp_time);
    }

    /**
     * Checks whether requested template exists.
     *
     * @param string $tpl_file
     *
     * @return bool
     * @throws \SmartyException
     * @deprecated
     */
    public function template_exists($tpl_file)
    {
        return $this->templateExists($tpl_file);
    }

    /**
     * Returns an array containing template variables
     *
     * @param string $name
     *
     * @return array
     * @deprecated
     */
    public function get_template_vars($name = null)
    {
        return $this->getTemplateVars($name);
    }

    /**
     * Returns an array containing config variables
     *
     * @param string $name
     *
     * @return array
     * @deprecated
     */
    public function get_config_vars($name = null)
    {
        return $this->getConfigVars($name);
    }

    /**
     * load configuration values
     *
     * @param string $file
     * @param string $section
     * @param string $scope
     * @deprecated
     */
    public function config_load($file, $section = null, $scope = 'global')
    {
        $this->ConfigLoad($file, $section, $scope);
    }

    /**
     * return a reference to a registered object
     *
     * @param string $name
     *
     * @return object
     * @deprecated
     */
    public function get_registered_object($name)
    {
        return $this->getRegisteredObject($name);
    }

    /**
     * clear configuration values
     *
     * @param string $var
     * @deprecated
     */
    public function clear_config($var = null)
    {
        $this->clearConfig($var);
    }

    /**
     * trigger Smarty error
     *
     * @param string  $error_msg
     * @param integer $error_type
     * @deprecated
     */
    public function trigger_error($error_msg, $error_type = E_USER_WARNING)
    {
        trigger_error("Smarty error: $error_msg", $error_type);
    }
}
