<?php if (!defined('APPLICATION')) exit;

$PluginInfo['test-old-plugin'] = array(
    'Name'        => "test-old-plugin",
    'Description' => "This is a fixture for unit testing.",
    'Version'     => '1.0.0',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'License'     => 'GPLv2'
);

/**
 * test-old-plugin Plugin
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2016 (c) Todd Burry
 * @license   GPLv2
 * @since     1.0.0
 */
class TestOldPluginPlugin extends Gdn_Plugin {
    /**
     * This will run when you "Enable" the plugin
     *
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function setup() {
        return true;
    }
}
