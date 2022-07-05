<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use Garden\PsrEventHandlersInterface;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Models\AddonDisabledEvent;

/**
 * Event handlers related to record statuses.
 */
class RecordStatusEventHandlers implements PsrEventHandlersInterface
{
    /** @var RecordStatusModel */
    private $recordStatusModel;

    /**
     * DI.
     *
     * @param RecordStatusModel $recordStatusModel
     */
    public function __construct(RecordStatusModel $recordStatusModel)
    {
        $this->recordStatusModel = $recordStatusModel;
    }

    /**
     * @return string[]
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleAddonDisabled"];
    }

    /**
     * Whenever an addon is disabled structure our addon active states.
     *
     * @param AddonDisabledEvent $event
     * @return AddonDisabledEvent
     */
    public function handleAddonDisabled(AddonDisabledEvent $event)
    {
        $this->recordStatusModel->structureActiveStates();
        return $event;
    }
}
