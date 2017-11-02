<?php
namespace {
    $PluginInfo['namespaced-plugin'] = [
        'Name'        => 'Namespaced Plugin',
        'Description' => 'Namespaced Fixture Plugin',
        'Version'     => '1.0.0',
        'Author'      => 'Alexandre (DaazKu) Chouinard',
        'AuthorEmail' => 'alexandre.c@vanillaforums.com',
        'License'     => 'GPLv2'
    ];
}

namespace Deeply\Nested\Namespaced\Fixture {
    class NamespacedPlugin extends \Gdn_Plugin {
        public function base_render_before() {
            echo __CLASS__.' is loaded!';
        }
    }

    class TestClass {}
}
