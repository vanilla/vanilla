<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Utility\ArrayUtils;

/** Filter for the openapi */
class ModerationMessagesFilterOpenApi
{
    private $messageModel;

    /**
     * DI.
     *
     * @param \MessageModel $messageModel
     */
    public function __construct(\MessageModel $messageModel)
    {
        $this->messageModel = $messageModel;
    }

    /**
     * Filter the openapi to add moderation messages parameters.
     *
     * @param array $openApi
     */
    public function __invoke(array &$openApi): void
    {
        $layoutViewTypes = $this->messageModel->getLayoutViewTypes();

        ArrayUtils::setByPath("components.schemas.LayoutViewType.enum", $openApi, $layoutViewTypes);
    }
}
