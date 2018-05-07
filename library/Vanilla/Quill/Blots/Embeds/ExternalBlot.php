<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

use Gdn;
use Gdn_Controller;
use HeadModule;
use Vanilla\Embeds\EmbedManager;

class ExternalBlot extends AbstractBlockEmbedBlot {

    /** @var EmbedManager */
    private $embedManager;

    /**
     * @inheritdoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);

        /** @var EmbedManager embedManager */
        $this->embedManager = Gdn::getContainer()->get(EmbedManager::class);
    }

    /**
     * Add client-side scripts to the current controller.
     *
     * @param array $scripts
     */
    private function addScripts(array $scripts) {
        /** @var Gdn_Controller $controller */
        $controller = Gdn::getContainer()->get(Gdn_Controller::class);
        $head = $controller->getHead();

        if ($head instanceof HeadModule) {
            foreach ($scripts as $script) {
                $head->addScript($script, 'text/javascript', false, ['defer' => 'true']);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected static function getInsertKey(): string {
        return "insert.embed-external";
    }

    /**
     * @inheritDoc
     */
    protected function renderContent(array $data): string {
        try {
            $rendered = $this->embedManager->renderData($data);

            $scripts = $this->embedManager->getScripts($data['type'] ?? '');
            if ($scripts) {
                $this->addScripts($scripts);
            }
        } catch (\Exception $e) {
            $rendered = '';
        }

        return $rendered;
    }
}
