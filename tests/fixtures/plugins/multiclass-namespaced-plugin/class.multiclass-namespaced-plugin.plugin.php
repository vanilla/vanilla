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

    class MultiClassNamespacedPluginSwarmClass {}
}

namespace Deeply\Nested\NamespacedFixture {
    class MultiClassNamespacedPluginSwarmClass {}
}

namespace Deeply\NestedNamespacedFixture {
    class MultiClassNamespacedPluginSwarmClass {}
}

namespace DeeplyNestedNamespacedFixture {
    class MultiClassNamespacedPluginSwarmClass {}
}

namespace DeeplyNested\NamespacedFixture {
    class MultiClassNamespacedPluginSwarmClass {}
}

namespace Deeply\NestedNamespaced\Fixture {
    class MultiClassNamespacedPluginSwarmClass {}
}

namespace Deeply\Nested\Namespaced {
    class MultiClassNamespacedPluginSwarmClass {}
}

namespace Deeply\Nested {
    class MultiClassNamespacedPluginSwarmClass {}
}

namespace Deeply {
    class MultiClassNamespacedPluginSwarmClass {}
}
