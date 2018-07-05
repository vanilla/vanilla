<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

use Gdn;
use Vanilla\Embeds\EmbedManager;
use Vanilla\Quill\Blots\AbstractBlot;

/**
 * Blot for rendering embeds with the embed manager.
 */
class ExternalBlot extends AbstractBlot {

    /** @var EmbedManager */
    private $embedManager;

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return (boolean) valr("insert.embed-external", $operations[0]);
    }

    /**
     * @inheritdoc
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);

        /** @var EmbedManager embedManager */
        $this->embedManager = Gdn::getContainer()->get(EmbedManager::class);
    }

    /**
     * Render out the content of the blot using the EmbedManager.
     * @see EmbedManager
     * @inheritDoc
     */
    public function render(): string {
        $value = $this->currentOperation["insert"]["embed-external"] ?? [];
        $data = $value['data'] ?? $value;
        try {
            return $this->embedManager->renderData($data);
        } catch (\Exception $e) {
            // TODO: Add better error handling here.
            return '';
        }
    }

    /**
     * Block embeds are always their own group.
     * @inheritDoc
     */
    public function isOwnGroup(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return "<div class='js-embed embedResponsive'>";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</div>";
    }
}
