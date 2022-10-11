<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\Models\CollectionModel;
use Vanilla\ApiUtils;
use Vanilla\Utility\SchemaUtils;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;

/**
 * Controller for the /collections endpoint
 */
class CollectionsApiController extends \AbstractApiController
{
    /** @var CollectionModel */
    protected $collectionModel;

    /** @var \LocalesApiController $localeApi */
    private $localeApi;

    /**
     * CollectionsApiController constructor.
     *
     * @param CollectionModel $collectionModel
     * @param \LocalesApiController $localeApi
     *
     */
    public function __construct(CollectionModel $collectionModel, \LocalesApiController $localeApi)
    {
        $this->collectionModel = $collectionModel;
        $this->localeApi = $localeApi;
    }

    /**
     * Get collections from a query string.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse([
            "collectionID?" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
            "name:s?",
            "page:i?" => [
                "description" => "Page number. [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of collection records.",
                "minimum" => 1,
                "default" => CollectionModel::LIMIT_DEFAULT,
            ],
        ]);
        $query = $in->validate($query);
        $options = [];
        [$options["offset"], $options["limit"]] = offsetLimit("p{$query["page"]}", $query["limit"]);
        $where = [];
        if (isset($query["collectionID"])) {
            $where["collectionID"] = $query["collectionID"];
        }
        if (isset($query["name"])) {
            $where["name"] = $query["name"];
        }
        $results = $this->collectionModel->searchCollectionRecords($where, $options);
        $out = Schema::parse([
            "collectionID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "records:a" => $this->collectionRecordSchema(),
        ]);
        SchemaUtils::validateArray($results, $out, true);

        $paging = ApiUtils::morePagerInfo($results, "/api/v2/collections/", $query, $in);

        return new Data($results, ["paging" => $paging]);
    }

    /**
     * Get a single collection and its records
     *
     * @param int $collectionID
     * @return Data
     */
    public function get(int $collectionID): Data
    {
        $this->permission("community.manage");
        $result = $this->collectionModel->getCollectionRecordByID($collectionID);
        $out = Schema::parse([
            "collectionID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "records:a" => $this->collectionRecordSchema(),
        ]);
        $result = $out->validate($result, true);

        return new Data($result);
    }

    /**
     * Delete a collection
     *
     * @param int $collectionID
     * @return void
     */
    public function delete(int $collectionID): void
    {
        $this->permission("community.manage");
        $this->collectionModel->selectSingle(["collectionID" => $collectionID]);
        $this->collectionModel->deleteCollection($collectionID);
    }

    /**
     * Create a new collection with records
     *
     * @param array $body
     * @return Data
     */
    public function post(array $body): Data
    {
        $this->permission("community.manage");
        $in = $this->postSchema();
        $body = $in->validate($body);
        // save the collection Record
        $collectionID = $this->collectionModel->saveCollection($body);

        return $this->get($collectionID);
    }

    /**
     * Get collection records and its extracted contents
     *
     * @param int $collectionID
     * @return Data
     */
    public function get_content(int $collectionID, string $locale): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse([
            "id:i" => "collectionID",
            "locale:s",
        ])->addValidator("locale", [$this->localeApi, "validateLocale"]);
        $in->validate(["id" => $collectionID, "locale" => $locale]);
        $result = $this->collectionModel->getCollectionRecordContentByID($collectionID, $locale);
        $out = Schema::parse([
            "collectionID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "records:a" => $this->collectionRecordSchema()->merge(Schema::parse(["record:o"])),
        ]);
        $result = $out->validate($result);
        return new Data($result);
    }

    /**
     * Update collections and its contents
     *
     * @param int $collectionID
     * @param array $body
     * @return Data
     */
    public function patch(int $collectionID, array $body): Data
    {
        $this->permission("community.manage");
        $in = $this->patchSchema();
        $body = $in->validate($body);

        $collectionRecord = $this->collectionModel->select(["collectionID" => $collectionID]);
        if (empty($collectionRecord)) {
            throw new NotFoundException("Collection");
        }
        $this->collectionModel->updateCollection($collectionID, $body);

        return $this->get($collectionID);
    }

    /**
     * @return Schema
     */
    private function postSchema(): Schema
    {
        return Schema::parse([
            "name:s" => ["minLength" => 1, "maxLength" => 255],
            "records" => [
                "type" => "array",
                "minItems" => 1,
                "maxItems" => 30,
                "items" => $this->collectionRecordSchema(),
            ],
        ])->addValidator("records", [$this->collectionModel, "validateCollectionRecords"]);
    }

    /**
     * @return Schema
     */
    private function patchSchema(): Schema
    {
        return Schema::parse(["name?", "records?"])
            ->add($this->postSchema())
            ->addValidator("records", [$this->collectionModel, "validateCollectionRecords"]);
    }

    /**
     * Get a schema representing collection records.
     *
     * @return Schema
     */
    private function collectionRecordSchema(): Schema
    {
        return Schema::parse([
            "recordID:i",
            "recordType:s" => ["enum" => $this->collectionModel->getAllRecordTypes()],
            "sort:i?",
        ]);
    }
}
