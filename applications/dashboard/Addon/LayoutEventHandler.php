<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Addon;

use Exception;
use Garden\EventHandlersInterface;
use Garden\PsrEventHandlersInterface;
use Garden\Web\Exception\NotFoundException;
use Generator;
use LocaleModel;
use MachineTranslation\Providers\LayoutTranslatableModel;
use MachineTranslation\Services\GptTranslationService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Dashboard\Events\LayoutEvent;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;
use Vanilla\Forum\Models\TranslationModel;
use Vanilla\Layout\LayoutModel;
use Vanilla\Logger;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Scheduler\TrackingSlipInterface;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\SystemCallableInterface;

/**
 * Event handler for layout translation.
 */
class LayoutEventHandler implements
    EventHandlersInterface,
    LoggerAwareInterface,
    SystemCallableInterface,
    psrEventHandlersInterface
{
    use LoggerAwareTrait;

    /**
     * @param LayoutModel $layoutModel
     * @param LongRunner $longRunner
     * @param TranslationModel $translationModel
     * @param CommunityMachineTranslationModel $communityTranslationModel
     */
    public function __construct(
        private LayoutModel $layoutModel,
        private LongRunner $longRunner,
        private TranslationModel $translationModel,
        private CommunityMachineTranslationModel $communityTranslationModel
    ) {
    }

    /**
     * @return string[]
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleLayoutEvent"];
    }

    /**
     * @inheridoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["translateLayouts"];
    }

    /**
     * Call LongRunner to translate the layout full layout record.
     *
     * @param string $recordType
     * @param int $recordID
     * @return TrackingSlipInterface|null
     */
    public function afterTranslation_handler(string $recordType, int $recordID): ?TrackingSlipInterface
    {
        if ($recordType != LayoutTranslatableModel::CONTENT_TYPE) {
            return null;
        }

        $locales = LocaleModel::getTranslatableLocales(false);
        $action = new LongRunnerAction(self::class, "translateLayouts", [$recordID, $locales]);
        return $this->longRunner->runDeferred($action);
    }

    /**
     * Rebuilt the layout with the translated values.
     *
     * @param int $recordID
     * @param array $locales
     * @return Generator|LongRunnerNextArgs
     * @throws NotFoundException
     */
    public function translateLayouts(int $recordID, array $locales): Generator|LongRunnerNextArgs
    {
        try {
            $layoutRecord = $this->layoutModel->getByID($recordID);
        } catch (NotFoundException $e) {
            // The record has been deleted before we got here.
            return LongRunner::FINISHED;
        }

        if (empty($locales)) {
            // There are no valid locale.
            return LongRunner::FINISHED;
        }

        try {
            $remainingLocales = $locales;
            foreach ($locales as $localIndex => $locale) {
                $layout["layout"] = $layoutRecord["layout"];
                $translatedComponents = $this->translationModel->getTranslationByRecord(
                    LayoutTranslatableModel::CONTENT_TYPE,
                    $recordID,
                    $locale
                );

                foreach ($translatedComponents as $translation) {
                    if ($translation["propertyName"] == "layout") {
                        // We've hit a fully translated layout.
                        continue;
                    }

                    $current = ArrayUtils::getByPath($translation["propertyName"], $layout);

                    if (empty($current)) {
                        // We don't translate empty string, so we must make sure to set it to empty string.
                        $layout = ArrayUtils::setByPath($translation["propertyName"], $layout, "");
                    } else {
                        $layout = ArrayUtils::setByPath(
                            $translation["propertyName"],
                            $layout,
                            $translation["translation"]
                        );
                    }
                }

                $this->translationModel->addTranslation(
                    CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
                    "layout",
                    $recordID,
                    $locale,
                    LayoutTranslatableModel::CONTENT_TYPE,
                    json_encode($layout["layout"], JSON_FORCE_OBJECT)
                );

                unset($remainingLocales[$localIndex]);
                yield new LongRunnerSuccessID($recordID . "-" . $locale);
            }
        } catch (LongRunnerTimeoutException $e) {
            return new LongRunnerNextArgs([$recordID, $remainingLocales]);
        } catch (Exception $e) {
            yield new LongRunnerFailedID($recordID . "-" . $locale);
            $this->logger->warning("Error translating layout $recordID", [
                "exception" => $e,
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["MachineTranslation"],
            ]);
        }

        return LongRunner::FINISHED;
    }

    /**
     * @param LayoutEvent $event
     * @return LayoutEvent
     * @throws NotFoundException
     */
    public function handleLayoutEvent(LayoutEvent $event): LayoutEvent
    {
        if (GptTranslationService::isEnabled()) {
            $payload = $event->getPayload();
            $this->communityTranslationModel->translate("layout", $payload["layout"]["layoutID"]);
        }

        return $event;
    }
}
