<?php if (!defined('APPLICATION')) exit;

$PluginInfo['test-old-plugin'] = [
    'Name'        => "test-old-plugin",
    'Description' => "This is a fixture for unit testing.",
    'Version'     => '1.0.0',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'License'     => 'GPLv2',
    'RequiredApplications' => [
        'test-old-application' => '1.0'
    ]
];

class TestOldPluginPlugin extends Gdn_Plugin {
    public function setup() {
        return true;
    }
}
