<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Exception;
use Garden\EventManager;
use Gdn;
use Generator;
use MachineTranslation\Providers\TranslatableModelInterface;
use MachineTranslation\Services\GptTranslationService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Logger;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Scheduler\TrackingSlipInterface;
use Vanilla\Web\SystemCallableInterface;

/**
 * Model used to manage machine translation.
 */
class CommunityMachineTranslationModel implements LoggerAwareInterface, SystemCallableInterface
{
    use LoggerAwareTrait;

    const TRANSLATION_RESOURCE = "machineTranslated";
    const FEATURE_FLAG = "Feature.machineTranslation.enabled";

    const TEXT_CHUNK_SIZE = 3000;
    /**
     * @var array of TranslatableModelInterface instances
     */
    public array $localeTranslationModel = [];

    public function __construct(
        private LongRunner $longRunner,
        private TranslationModel $translationModel,
        private TranslationPropertyModel $translationPropertyModel,
        private EventManager $eventManager,
        private GptTranslationService $translationService
    ) {
    }

    /**
     * @param TranslatableModelInterface $translateModel
     */
    public function addTranslatableModel(TranslatableModelInterface $translateModel): void
    {
        $this->localeTranslationModel[$translateModel->getContentType()] = $translateModel;
    }

    /**
     * Get content types.
     *
     * @return array
     */
    public function getTranslateModels(): array
    {
        return array_keys($this->localeTranslationModel);
    }

    /**
     * @inheridoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["generateTranslations"];
    }

    /**
     * Start the process to generate translations by calling longRunner.
     *
     * @param string $recordType
     * @param int|array $recordIDs
     *
     * @return TrackingSlipInterface|false
     */
    public function translate(string $recordType, int|array $recordIDs): TrackingSlipInterface|false
    {
        $this->translationService = Gdn::getContainer()->get(GptTranslationService::class);
        if (!$this->translationService->isEnabled()) {
            return false;
        }

        if (!in_array($recordType, $this->getTranslateModels())) {
            return false;
        }

        if (!is_array($recordIDs)) {
            $recordIDs = [$recordIDs];
        }

        $action = new LongRunnerAction(self::class, "generateTranslations", [$recordType, $recordIDs]);
        return $this->longRunner->runDeferred($action);
    }

    /**
     * A long runner job to translate multiple records.
     *
     * @param string $recordType
     * @param array $recordIDs
     * @return Generator|LongRunnerNextArgs
     */
    public function generateTranslations(string $recordType, array $recordIDs): Generator|LongRunnerNextArgs
    {
        try {
            $remainingRecordIDs = $recordIDs;
            foreach ($recordIDs as $index => $recordID) {
                $record = $this->getTranslatableRecord($recordType, $recordID);

                if (!$record) {
                    // The record has been deleted before we got here, or we got here because the record was deleted, so delete translations.
                    $this->deleteTranslation($recordType, $recordID);
                    unset($remainingRecordIDs[$index]);
                    yield new LongRunnerSuccessID($recordID);
                    continue;
                }

                // Make sure there is at least one non-empty field to translate.
                foreach ($record as $recordField => $recordValue) {
                    if (empty($recordValue)) {
                        unset($record[$recordField]);
                    }
                }

                if (empty($record)) {
                    unset($remainingRecordIDs[$index]);
                    yield new LongRunnerSuccessID($recordID);
                    continue;
                }
                $translationKeys = [];
                $originalLanguage = $this->translationService->generateOriginalLanguage($record);
                foreach ($record as $translationPropertyKey => $text) {
                    $batch = explode(" ", $text);
                    $textParts = [];
                    $temp = "";
                    foreach ($batch as $word) {
                        $temp .= (empty($temp) ? "" : " ") . $word;
                        if (strlen($temp) > self::TEXT_CHUNK_SIZE) {
                            $textParts[] = $temp;
                            $temp = "";
                        }
                    }
                    $textParts[] = $temp;
                    $translation = [];
                    foreach ($textParts as $textPart) {
                        $translations = $this->translationService->translate([$textPart], $originalLanguage);
                        foreach ($translations["translation"] as $locale => $translatedText) {
                            if (!array_key_exists($locale, $translation)) {
                                $translation[$locale] = "";
                            }
                            $translation[$locale] .=
                                (empty($translation[$locale]) ? "" : " ") . is_array($translatedText)
                                    ? $translatedText[0]
                                    : $translatedText;
                        }
                    }
                    foreach ($translation as $locale => $translationText) {
                        $this->manageTranslationProperty(
                            self::TRANSLATION_RESOURCE,
                            $recordType,
                            $recordID,
                            $translationPropertyKey,
                            $translationKeys
                        );
                        $this->translationModel->createTranslation(
                            CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
                            $locale,
                            $translationKeys[$translationPropertyKey],
                            $translationText
                        );
                    }
                    unset($remainingRecordIDs[$index]);

                    // Send out an event to do post-translation processing.
                    $this->eventManager->fire("afterTranslation", $recordType, $recordID);
                }

                yield new LongRunnerSuccessID($recordID);
            }
        } catch (LongRunnerTimeoutException $e) {
            return new LongRunnerNextArgs([$recordType, $remainingRecordIDs]);
        } catch (Exception $e) {
            yield new LongRunnerFailedID($recordID);
            $this->logger->warning("Error Throws saving translations", [
                "exception" => $e,
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["MachineTranslation"],
            ]);
        }

        return LongRunner::FINISHED;
    }

    /**
     * Delete the translations records.
     *
     * @param string $recordType
     * @param int $recordID
     * @return void
     * @throws Exception
     */
    public function deleteTranslation(string $recordType, int $recordID): void
    {
        $translationProperties = $this->translationPropertyModel->select([
            "recordID" => $recordID,
            "recordType" => $recordType,
            "resource" => CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
        ]);

        $translationPropertyKey = array_column($translationProperties, "translationPropertyKey");

        $this->translationModel->delete([
            "resource" => CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
            "translationPropertyKey" => $translationPropertyKey,
        ]);
        $this->translationPropertyModel->delete([
            "recordID" => $recordID,
            "recordType" => $recordType,
            "resource" => CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
        ]);
    }

    /**
     * Make sure the translation property exists. Create it if it doesn't.
     *
     * @param string $recordType
     * @param int $recordID
     * @param string $locale
     * @param string $key
     * @param array $translationKeys
     * @return void
     */
    private function manageTranslationProperty(
        string $resource,
        string $recordType,
        int $recordID,
        string $key,
        array &$translationKeys
    ): void {
        if (!array_key_exists($key, $translationKeys)) {
            $translationProperty = $this->translationPropertyModel->getTranslationProperty([
                "recordID" => $recordID,
                "recordType" => $recordType,
                "propertyName" => $key,
            ]);
            if (!$translationProperty) {
                $translationProperty = $this->translationPropertyModel->createTranslationProperty($resource, [
                    "recordID" => $recordID,
                    "recordType" => $recordType,
                    "propertyName" => $key,
                    "resource" => CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
                ]);
            }
            $translationKey = $translationProperty["translationPropertyKey"];
            $translationKeys[$key] = $translationKey;
        }
    }

    /**
     * Get the record to translate.
     *
     * @param string $recordType
     * @param string $recordID
     * @return array|false
     * @throws Exception
     */
    private function getTranslatableRecord(string $recordType, string $recordID): array|false
    {
        if ($localeModel = $this->localeTranslationModel[$recordType] ?? false) {
            return $localeModel->getContentToTranslate($recordID);
        }
        throw new Exception("Record type not registered for translation.");
    }

    /**
     * Replace translated content.
     *
     * @param string $recordType
     * @param array $data
     * @param string $locale
     *
     * @return array|false
     * @throws Exception
     */
    public function replaceTranslatableRecord(string $recordType, array $data, string $locale): array|false
    {
        if (in_array(substr($recordType, 0, -1), $this->getTranslateModels())) {
            $recordType = substr($recordType, 0, -1);
        }
        $singleRecord = false;
        if ($localeModel = $this->localeTranslationModel[$recordType] ?? false) {
            $dataKey = $localeModel->getObjectKey($data);
            $result = $data;
            if (!empty($dataKey)) {
                $result = $data[$dataKey];
            }

            if (array_key_exists($localeModel->getPrimaryKey(), $result)) {
                $result = [$result];
                $singleRecord = true;
            }

            $result = $this->translationModel->translateProperties(
                $locale,
                CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
                $recordType,
                $localeModel->getPrimaryKey(),
                $result,
                $localeModel->getContentKeysToTranslate()
            );

            if (!empty($dataKey)) {
                $data[$dataKey] = $singleRecord ? $result[0] : $result;
            } else {
                $data = $singleRecord ? $result[0] : $result;
            }
        }
        return $data;
    }
}
