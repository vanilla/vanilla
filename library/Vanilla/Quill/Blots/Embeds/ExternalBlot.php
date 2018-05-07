<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

use Gdn;
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
        } catch (\Exception $e) {
            $rendered = '';
        }

        return $rendered;
    }
}
