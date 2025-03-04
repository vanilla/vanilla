<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\TranslationItem;
use Vanilla\Forum\Models\ResourceModel;
use Vanilla\Forum\Models\TranslationModel;
use Vanilla\Forum\Models\TranslationPropertyModel;

/**
 * Class TranslationsApiController
 */
class TranslationsApiController extends AbstractApiController
{
    /** @var Schema */
    private $resourceSchema;

    /** @var Schema */
    private $getResourceSchema;

    /** @var Schema */
    private $patchTranslationSchema;

    /** @var Schema */
    private $translationSchema;

    /** @var Schema */
    private $deleteTranslationSchema;

    /** @var ResourceModel */
    private $resourceModel;

    /** @var TranslationModel */
    private $translationModel;

    /** @var ConfigurationInterface */
    private $config;

    /** @var TranslationPropertyModel */
    private $translationPropertyModel;

    /** @var LocalesApiController $localeApi */
    private $localeApi;

    /**
     * TranslationsApiController constructor.
     *
     * @param ResourceModel $resourcesModel
     * @param TranslationModel $translationModel
     * @param TranslationPropertyModel $translationPropertyModel
     * @param ConfigurationInterface $configurationModule
     * @param LocalesApiController $localeApi
     */
    public function __construct(
        ResourceModel $resourcesModel,
        TranslationModel $translationModel,
        TranslationPropertyModel $translationPropertyModel,
        ConfigurationInterface $configurationModule,
        LocalesApiController $localeApi
    ) {
        $this->resourceModel = $resourcesModel;
        $this->translationModel = $translationModel;
        $this->translationPropertyModel = $translationPropertyModel;
        $this->config = $configurationModule;
        $this->localeApi = $localeApi;
    }

    /**
     * Create a resource.
     *
     * @param array $body
     * @throws ClientException
     */
    public function post(array $body = [])
    {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->resourceSchema("in");
        $body = $in->validate($body);

        $body["sourceLocale"] = $body["sourceLocale"] ?? $this->config->get("Garden.Locale");

        $resourceExists = $this->resourceModel->get([
            "name" => $body["name"],
            "sourceLocale" => $body["sourceLocale"],
            "urlCode" => $body["urlCode"],
        ]);

        if ($resourceExists) {
            throw new ClientException(
                "The resource " . $body["urlCode"] . "-" . $body["sourceLocale"] . "-" . $body["name"] . " exists"
            );
        } else {
            $this->resourceModel->insert($body);
        }
    }

    /**
     * PATCH /translations/:resourceUrlCode
     *
     * @param string $path Resource slug
     * @param array $body
     */
    public function patch(string $path, array $body = [])
    {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema([":a" => $this->patchTranslationSchema()], "in");
        $path = substr($path, 1);

        $records = $in->validate($body);

        foreach ($records as $record) {
            $this->resourceModel->ensureResourceExists($path);
            $resourceKeyRecord = array_intersect_key($record, TranslationPropertyModel::RESOURCE_KEY_RECORD);

            $translationProperty = $this->translationPropertyModel->getTranslationProperty($resourceKeyRecord);

            if (!$translationProperty) {
                $newTranslationProperty = $this->translationPropertyModel->createTranslationProperty(
                    $path,
                    $resourceKeyRecord
                );
                $key = $newTranslationProperty["translationPropertyKey"];
            } else {
                $key = $translationProperty["translationPropertyKey"];
            }

            $this->translationModel->createTranslation($path, $record["locale"], $key, $record["translation"]);

            $this->translationModel->notifyResourceUpdate(
                $path,
                new TranslationItem($key, $record["locale"], $record["translation"])
            );
        }
    }

    /**
     * GET /translations/:resourceUrlCode
     *
     * @param string $path
     * @param array $query
     * @return array
     */
    public function get(string $path, array $query = [])
    {
        $this->permission("Garden.Moderation.Manage");
        $path = substr($path, 1);

        $in = $this->getTranslationsSchema("in");
        if ($query["validateLocale"] ?? true) {
            $in->addValidator("locale", [$this->localeApi, "validateLocale"]);
        }

        $query["resourceUrlCode"] = $path;
        $query = $in->validate($query);

        $where["tp.resource"] = $query["resourceUrlCode"];

        if (isset($query["recordType"])) {
            $where["tp.recordType"] = $query["recordType"];

            if (isset($query["recordIDs"])) {
                $where["tp.recordID"] = $query["recordIDs"];
            }

            if (isset($query["recordKeys"])) {
                $where["tp.recordKey"] = $query["recordKeys"];
            }
        }

        if (isset($query["propertyName"])) {
            $where["tp.propertyName"] = $query["propertyName"];
        }

        if (isset($query["locale"])) {
            $where["t.locale"] = $query["locale"];
        }

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);
        $options = [
            "limit" => $limit,
            "offset" => $offset,
        ];

        $results = $this->translationPropertyModel->getTranslations($where, $options);

        $out = $this->schema([":a" => $this->translationSchema()], "out");
        $results = $out->validate($results);

        return $results;
    }

    /**
     * PATCH /translations/:resourceUrlCode/remove
     *
     * @param string $path
     * @param array $body
     */
    public function patch_remove(string $path, array $body)
    {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema([":a" => $this->deleteTranslationSchema()], "in");
        $path = substr($path, 1);

        $records = $in->validate($body);

        foreach ($records as $record) {
            $this->resourceModel->ensureResourceExists($path);
            $translationProperty = $this->translationPropertyModel->getTranslationProperty($record);
            if ($translationProperty) {
                $this->translationModel->delete([
                    "translationPropertyKey" => $translationProperty["translationPropertyKey"],
                    "locale" => $record["locale"],
                ]);
            }
        }
    }

    /**
     * Simplified resource schema.
     *
     * @param string $type
     * @return Schema
     */
    public function resourceSchema(string $type = ""): Schema
    {
        if ($this->resourceSchema === null) {
            $this->resourceSchema = $this->schema(Schema::parse(["name?", "sourceLocale?", "urlCode"]));
        }
        return $this->schema($this->resourceSchema, $type);
    }

    /**
     * Simplified resource schema.
     *
     * @param string $type
     * @return Schema
     */
    public function getTranslationsSchema(string $type = ""): Schema
    {
        if ($this->getResourceSchema === null) {
            $this->getResourceSchema = $this->schema(
                Schema::parse([
                    "resourceUrlCode:s?",
                    "recordType:s?",
                    "recordIDs:a?" => [
                        "items" => ["type" => "integer"],
                    ],
                    "recordKeys:a?" => [
                        "items" => ["type" => "string"],
                    ],
                    "propertyName:s?",
                    "locale:s",
                    "limit:i?" => [
                        "default" => 100,
                        "minimum" => 1,
                        "maximum" => 100,
                    ],
                    "page:i?" => [
                        "description" =>
                            "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                        "default" => 1,
                        "minimum" => 1,
                        "maximum" => 100,
                    ],
                    "validateLocale:b?" => [
                        "description" => "Apply validation to locale.",
                        "type" => "boolean",
                        "default" => true,
                    ],
                ])
            );
        }
        return $this->schema($this->getResourceSchema, $type);
    }

    /**
     * Post translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function patchTranslationSchema(string $type = ""): Schema
    {
        if ($this->patchTranslationSchema === null) {
            $this->patchTranslationSchema = $this->schema(
                Schema::parse([
                    "resourceUrlCode:s?",
                    "recordType:s",
                    "recordID:i?",
                    "recordKey:s?",
                    "locale:s",
                    "propertyName:s",
                    "translation:s",
                ])
            );
        }
        return $this->schema($this->patchTranslationSchema, $type);
    }

    /**
     * Post translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function deleteTranslationSchema(string $type = ""): Schema
    {
        if ($this->deleteTranslationSchema === null) {
            $this->deleteTranslationSchema = $this->schema(
                Schema::parse(["recordType:s", "recordID:i?", "recordKey:s?", "locale:s", "propertyName:s"])
            );
        }
        return $this->schema($this->deleteTranslationSchema, $type);
    }

    /**
     * simple translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function translationSchema(string $type = ""): Schema
    {
        if ($this->translationSchema === null) {
            $this->translationSchema = $this->schema(
                Schema::parse([
                    "resource:s",
                    "recordType:s",
                    "recordID:i?",
                    "recordKey:s?",
                    "propertyName:s",
                    "propertyType:s?",
                    "translationPropertyKey:s",
                    "locale:s",
                    "translation:s",
                    "dateUpdated:dt",
                ])
            );
        }
        return $this->schema($this->translationSchema, $type);
    }
}
