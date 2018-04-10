<?php
namespace {
    $PluginInfo['multiclass-namespaced-plugin'] = [
        'Name'        => 'Multi Class Namespaced Plugin',
        'Description' => 'Multi Class Namespaced Fixture Plugin',
        'Version'     => '1.0.0',
        'Author'      => 'Alexandre (DaazKu) Chouinard',
        'AuthorEmail' => 'alexandre.c@vanillaforums.com',
        'License'     => 'GPLv2'
    ];
}

namespace Deeply\Nested\Namespaced\Fixture {
    class MultiClassNamespacedPlugin extends \Gdn_Plugin {
        public function base_render_before() {
            echo __CLASS__.' is loaded!';
        }
    }

    class MultiClassPluginHelper {}
}

namespace Deeply\Nested\Namespaced {
    class MultiClassPluginHelper {}
}

namespace Deeply\Nested {
    class MultiClassPluginHelper {}
}

namespace Deeply {
    class MultiClassPluginHelper {}
    class TestClass {}
}
