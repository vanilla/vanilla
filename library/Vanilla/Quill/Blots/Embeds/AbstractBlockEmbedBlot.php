<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

use Vanilla\Quill\Blots\AbstractBlot;
use Vanilla\Quill\BlotGroup;

abstract class AbstractBlockEmbedBlot extends AbstractBlot {

    /**
     * Get the key to pull the main content out of the currentBlot.
     *
     * @return string
     */
    abstract protected static function getInsertKey(): string;

    public function isOwnGroup(): bool {
        return true;
    }

    /**
     * Render the content of the blot. You should implement this instead of overriding render().
     *
     * @see AbstractBlockEmbedBlot::render()
     * @param array $data The data to render to embed from.
     *
     * @return string
     */
    abstract protected function renderContent(array $data): string;

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return (boolean) valr(static::getInsertKey(), $operations[0]);
    }

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $data = valr(static::getInsertKey(), $this->currentOperation);

        return $this->renderContent($data);
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "";
    }
}
