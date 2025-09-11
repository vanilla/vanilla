<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Exception;
use Garden\Web\Exception\ClientException;
use Gdn;
use Gdn_Database;
use Gdn_Session;
use LocalesApiController;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\TranslationItem;
use Vanilla\Contracts\Site\TranslationResourceInterface;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Manage translated content.
 */
class TranslationModel extends PipelineModel
{
    /** @var TranslationResourceInterface[] */
    private array $validResources = [];

    /**
     * TranslationModel constructor.
     *
     * @param Gdn_Session $session
     * @param LocalesApiController $localesApiController
     * @param ConfigurationInterface $config
     * @param ResourceModel $resourceModel
     * @param TranslationPropertyModel $translationPropertyModel
     */
    public function __construct(
        private Gdn_Session $session,
        private LocalesApiController $localesApiController,
        private ConfigurationInterface $config,
        private ResourceModel $resourceModel,
        private TranslationPropertyModel $translationPropertyModel
    ) {
        parent::__construct("translation");

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Create a translation record.
     *
     * @param string $resource
     * @param string $locale
     * @param string $translationPropertyKey
     * @param string $translationString
     *
     * @return bool
     * @throws ClientException
     */
    public function createTranslation(
        string $resource,
        string $locale,
        string $translationPropertyKey,
        string $translationString
    ): bool {
        $sourceLocale = $this->config->get("Garden.Locale");
        if ($locale !== $sourceLocale && !$this->validateLocale($locale)) {
            // This locale is not supported.
            return false;
        }

        $translation = $this->get([
            "resource" => $resource,
            "translationPropertyKey" => $translationPropertyKey,
            "locale" => $locale,
        ]);

        if (!$translation) {
            $translationRecord = [
                "resource" => $resource,
                "translationPropertyKey" => $translationPropertyKey,
                "locale" => $locale,
                "translation" => $translationString,
            ];
            $result = $this->insert($translationRecord);
        } else {
            $result = $this->update(
                ["translation" => $translationString],
                ["resource" => $resource, "translationPropertyKey" => $translationPropertyKey, "locale" => $locale]
            );
        }

        return $result;
    }

    /**
     * Validate a locale exists.
     * @param string $locale
     * @return bool
     */
    protected function validateLocale(string $locale): bool
    {
        $locales = $this->localesApiController->index();
        $availableLocales = array_column($locales, "localeKey");
        $availableRegionalLocales = array_column($locales, "regionalKey");

        return in_array($locale, $availableLocales) || in_array($locale, $availableRegionalLocales);
    }

    /**
     * @inheritdoc
     */
    public function initializeResource(TranslationResourceInterface $resource): void
    {
        $this->validResources[] = $resource;
        try {
            $resourceExists = $this->resourceModel->ensureResourceExists($resource->resourceKey(), false);
        } catch (Exception $e) {
            // The resource table may not exist yet.
            $this->resourceModel->structure(Gdn::database());
            $resourceExists = false;
        }

        if (!$resourceExists) {
            $this->resourceModel->insert($resource->resourceRecord());
        }
    }

    /**
     * Notify resource about being updated.
     *
     * @param string $resourceKey
     * @param TranslationItem $translationItem
     */
    public function notifyResourceUpdate(string $resourceKey, TranslationItem $translationItem)
    {
        foreach ($this->validResources as $resource) {
            if ($resource->resourceKey() === $resourceKey) {
                $resource->notify($translationItem);
                break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function translate(string $propertyKey, string $sourceValue): string
    {
        return Gdn::translate($propertyKey, $sourceValue);
    }

    /**
     * Translate properties of some recordType items provided
     *
     * @param string $locale
     * @param string $resource
     * @param string $recordType Ex: discussion, knwoledgeCategory
     * @param string $idFieldName Ex: discussionID, categoryID, knowldegeCategoryID, etc
     * @param array $records
     * @param array $properties Ex: ['name', 'description']
     * @return array
     */
    public function translateProperties(
        string $locale,
        string $resource,
        string $recordType,
        string $idFieldName,
        array $records,
        array $properties
    ): array {
        $where = [
            "t.locale" => $locale,
            "tp.resource" => $resource,
            "tp.recordType" => $recordType,
            "tp.propertyName" => $properties,
        ];
        $ids = array_column($records, $idFieldName);
        if (count($ids) > 0) {
            $where["tp.recordID"] = $ids;

            $translations = $this->translationPropertyModel->translateProperties($where, $properties);
            if (count($translations) > 0) {
                foreach ($records as &$record) {
                    foreach ($properties as $property) {
                        if (
                            is_array($record) &&
                            key_exists($property, $record) &&
                            !empty(($translation = $translations[$record[$idFieldName]][$property] ?? null))
                        ) {
                            $record[$property] = is_array($record[$property])
                                ? json_decode($translation, true)
                                : $translation;
                        }
                    }
                }
            }
        }

        return $records;
    }

    /**
     * Structure our database schema.
     *
     * @param Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop
     * @return void
     * @throws Exception
     */
    public static function structure(Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("translation")
            ->column("resource", "varchar(64)", false, ["index", "unique.translation"])
            ->column("translationPropertyKey", "varchar(255)", false, ["index", "unique.translation"])
            ->column("locale", "varchar(10)", false, ["index", "index.translation", "unique.translation"]) // Warned for a something they posted?
            ->column("translation", "mediumtext", false)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set($explicit, $drop);
    }

    /**
     * Get all translationsProperties for a record.
     *
     * @param string $recordType
     * @param int $recordID
     * @param string $locale
     * @return array
     * @throws Exception
     */
    public function getTranslationByRecord(string $recordType, int $recordID, string $locale): array|false
    {
        $sql = $this->database->createSql();
        $result = $sql
            ->select(["t.translation", "tp.propertyName"])
            ->from("translation t")
            ->join("translationProperty tp", "t.translationPropertyKey = tp.translationPropertyKey", "inner")
            ->where("tp.recordType", $recordType)
            ->where("tp.recordID", $recordID)
            ->where("t.locale", $locale)
            ->get()
            ->resultArray();

        return $result;
    }

    /**
     * Add a new translation record.
     *
     * @param string $recordType
     * @param int $recordID
     * @param string $locale
     * @param string $propertyName
     * @param string $translationValue
     * @return bool success
     * @throws ClientException
     */
    public function addTranslation(
        string $resource,
        string $recordType,
        int $recordID,
        string $locale,
        string $propertyName,
        string $translationValue
    ): bool {
        $translationProperty = $this->translationPropertyModel->getTranslationProperty([
            "recordID" => $recordID,
            "recordType" => $recordType,
            "propertyName" => $propertyName,
        ]);
        if (!$translationProperty) {
            $translationProperty = $this->translationPropertyModel->createTranslationProperty($resource, [
                "recordID" => $recordID,
                "recordType" => $recordType,
                "propertyName" => $propertyName,
                "resource" => $resource,
            ]);
        }

        return $this->createTranslation(
            CommunityMachineTranslationModel::TRANSLATION_RESOURCE,
            $locale,
            $translationProperty["translationPropertyKey"],
            $translationValue
        );
    }
}
