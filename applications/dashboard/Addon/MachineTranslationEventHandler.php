<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Addon;

use Garden\EventHandlersInterface;
use Garden\PsrEventHandlersInterface;
use MachineTranslation\Services\GptTranslationService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Community\Events\MachineTranslationEvent;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;

/**
 * Event handler for recordType translation.
 */
class MachineTranslationEventHandler implements EventHandlersInterface, LoggerAwareInterface, psrEventHandlersInterface
{
    use LoggerAwareTrait;

    /**
     * @param CommunityMachineTranslationModel $communityTranslationModel
     */
    public function __construct(private CommunityMachineTranslationModel $communityTranslationModel)
    {
    }

    /**
     * @return string[]
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleMachineTranslationEvent"];
    }

    /**
     * @param MachineTranslationEvent $event
     * @return MachineTranslationEvent
     */
    public function handleMachineTranslationEvent(MachineTranslationEvent $event): MachineTranslationEvent
    {
        if (GptTranslationService::isEnabled()) {
            $this->communityTranslationModel->translate($event->getRecordType(), $event->getRecordIDs());
        }

        return $event;
    }
}
