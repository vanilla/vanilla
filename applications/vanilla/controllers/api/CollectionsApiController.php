<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Pagination;
use LocalesApiController;
use PHPUnit\Exception;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\CollectionModel;
use Vanilla\ApiUtils;
use Vanilla\Models\CollectionRecordModel;
use Vanilla\Schema\RangeExpression;
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

    /** @var CollectionRecordModel */
    protected $collectionRecordModel;

    /** @var LocalesApiController $localeApi */
    private $localeApi;

    /**
     * CollectionsApiController constructor.
     *
     * @param CollectionModel $collectionModel
     * @param LocalesApiController $localeApi
     *
     */
    public function __construct(
        CollectionModel $collectionModel,
        CollectionRecordModel $collectionRecordModel,
        LocalesApiController $localeApi
    ) {
        $this->collectionModel = $collectionModel;
        $this->collectionRecordModel = $collectionRecordModel;
        $this->localeApi = $localeApi;
    }

    /**
     * Get collections from a query string.
     *
     * @param array $query
     * @return Data
     * @throws ValidationException
     * @throws HttpException
     * @throws PermissionException
     */
    public function index(array $query): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse([
            "collectionID?" => RangeExpression::createSchema([":int"]),
            "name:s?",
            "dateUpdated?" => new DateFilterSchema([
                "description" => "When the collection was updated.",
                "x-filter" => [
                    "field" => "DateUpdated",
                    "processor" => [DateFilterSchema::class, "dateFilterField"],
                ],
            ]),
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

        $where = ApiUtils::queryToFilters($in, $query);
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
            "records:a" => $this->collectRecordGetSchema(),
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
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get(int $collectionID): Data
    {
        $this->permission("community.manage");
        $result = $this->collectionModel->getCollectionRecordByCollectionID($collectionID);
        $out = Schema::parse([
            "collectionID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "records:a" => $this->collectRecordGetSchema(),
        ]);
        $result = $out->validate($result, true);

        return new Data($result);
    }

    /**
     * Get a set of collections containing a specified resource.
     *
     * @param array $query
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_byResource(array $query): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse(["recordType:s", "recordID:i"]);

        $validatedBody = $in->validate($query);
        $collections = $this->collectionModel->getCollectionsByRecord($validatedBody);
        $out = $this->schema([":a" => $this->getCollectionSchema()], "out");
        $result = $out->validate($collections);
        return new Data($result);
    }

    /**
     * Add a resource to one or more collections.
     *
     * @param array $body
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function put_byResource(array $body): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse([
            "record:o" => $this->collectionRecordSchema(),
            "collectionIDs:a" => ["items" => "int"],
        ]);

        $validatedBody = $in->validate($body);

        foreach ($validatedBody["collectionIDs"] as $collectionID) {
            try {
                $this->collectionModel->addCollectionRecords($collectionID, [$validatedBody["record"]]);
            } catch (Exception $ex) {
                ErrorLogger::error(
                    "Failed to add record {$validatedBody["record"]["recordType"]}_{$validatedBody["record"]["recordID"]} to collection {$collectionID}",
                    ["collections"]
                );
            }
        }

        $collections = $this->get_byResource([
            "recordType" => $validatedBody["record"]["recordType"],
            "recordID" => $validatedBody["record"]["recordID"],
        ]);

        $allCollectionIDs = array_column($collections->getData(), "collectionID");
        $removeRecordsWhere = array_diff($allCollectionIDs, $validatedBody["collectionIDs"]);

        $this->collectionModel->removeRecordFromCollections($validatedBody["record"], $removeRecordsWhere);

        $updatedCollections = $this->get_byResource([
            "recordType" => $validatedBody["record"]["recordType"],
            "recordID" => $validatedBody["record"]["recordID"],
        ]);

        return $updatedCollections;
    }

    /**
     * @param string $locale
     * @param array $query
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_contents(string $locale, array $query): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse([
            "collectionID?" => RangeExpression::createSchema([":int"]),
            "dateAddedToCollection?" => new DateFilterSchema([
                "description" => "Date a record has been added to collection.",
                "x-filter" => [
                    "field" => "dateInserted",
                    "processor" => [DateFilterSchema::class, "dateFilterField"],
                ],
            ]),
            "page:i?" => [
                "description" => "Page number. [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of collection records.",
                "minimum" => 1,
                "default" => CollectionRecordModel::LIMIT_DEFAULT,
            ],
            "expand?" => ApiUtils::getExpandDefinition(["collection"]),
        ])->addValidator("locale", [$this->localeApi, "validateLocale"]);

        if (!$this->localeApi->isValidLocale($locale)) {
            throw new ClientException("Invalid locale provided.");
        }
        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $expand = $query["expand"] ?? [];

        $where = ApiUtils::queryToFilters($in, $query);

        if (!empty($query["collectionID"])) {
            $where["collectionID"] = $query["collectionID"];
        }

        $totalRecordCount = $this->collectionRecordModel->getCount($where);
        $results = [];
        if ($totalRecordCount > 0) {
            $results = $this->collectionRecordModel->getCollectionRecords($where, $limit, $offset);
            $results = $this->collectionModel->filterCollectionRecords($results, $locale, false);
            if (!empty("collection") && $this->isExpandField("collection", $expand)) {
                $results = $this->expandCollection($results);
            }
            $out = $this->schema([":a" => $this->collectionContentsSchema()], "out");

            $results = $out->validate($results);
        }

        // When crawling the endpoint use a more pager.
        $paging =
            $totalRecordCount === 0
                ? ApiUtils::morePagerInfo($results, "/api/v2/collections/contents", $query, $in)
                : ApiUtils::numberedPagerInfo($totalRecordCount, "/api/v2collections/contents", $query, $in);
        $pagingObject = Pagination::tryCursorPagination($paging, $query, $results, "");
        return new Data($results, $pagingObject);
    }

    /**
     * Delete a collection
     *
     * @param int $collectionID
     * @return void
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     * @throws NoResultsException
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
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     * @throws ClientException
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
     * @param string $locale
     * @return Data
     * @throws ValidationException|NoResultsException
     */
    public function get_content(int $collectionID, string $locale): Data
    {
        $in = Schema::parse([
            "id:i" => "collectionID",
            "locale:s",
        ])->addValidator("locale", [$this->localeApi, "validateLocale"]);
        $in->validate(["id" => $collectionID, "locale" => $locale]);
        $results = $this->collectionModel->getCollectionRecordContentByID($collectionID, $locale);
        $out = Schema::parse([
            "collectionID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "records:a" => $this->collectRecordGetSchema()->merge(Schema::parse(["record:o"])),
        ]);
        $results = $out->validate($results);
        return new Data($results);
    }

    /**
     * Update collections and its contents
     *
     * @param int $collectionID
     * @param array $body
     * @return Data
     * @throws HttpException
     * @throws NoResultsException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function patch(int $collectionID, array $body): Data
    {
        $this->permission("community.manage");
        $in = $this->patchSchema($collectionID);
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
        ])
            ->addValidator("records", [$this->collectionModel, "validateCollectionRecords"])
            ->addValidator("name", $this->collectionModel->validateCollectionName());
    }

    /**
     * @return Schema
     */
    private function patchSchema(int $collectionID): Schema
    {
        return Schema::parse(["name?", "records?"])
            ->add($this->postSchema())
            ->addValidator("records", [$this->collectionModel, "validateCollectionRecords"])
            ->addValidator("name", $this->collectionModel->validateCollectionName($collectionID));
    }

    /**
     * Get the collection schema.
     *
     * @return Schema
     */
    private function getCollectionSchema(): Schema
    {
        return Schema::parse([
            "collectionID:i",
            "name:s",
            "insertUserID:i",
            "updateUserID:i|n",
            "dateInserted:dt",
            "dateUpdated:dt|n",
        ]);
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

    /**
     * Get a schema for the collection record display.
     *
     * @return Schema
     */
    private function collectRecordGetSchema(): Schema
    {
        return Schema::parse([
            "collectionID:i",
            "recordID:i",
            "recordType:s" => ["enum" => $this->collectionModel->getAllRecordTypes()],
            "dateAddedToCollection:dt",
            "sort:i?",
        ]);
    }

    /**
     * Get the schema for the collection contents.
     *
     * @return Schema
     */
    private function collectionContentsSchema(): Schema
    {
        return Schema::parse([
            "collectionID:i",
            "recordType:s",
            "recordID:i",
            "dateAddedToCollection:dt",
            "sort:i",
            "collection:o?" => Schema::parse(["collectionID:i", "name:s"]),
            "record:o",
        ]);
    }

    /**
     * Expand collection record
     *
     * @param array $results
     * @return array
     */
    private function expandCollection(array $results): array
    {
        foreach ($results as &$result) {
            $result["collection"] = [
                "collectionID" => $result["collectionID"],
                "name" => $result["name"],
            ];
        }
        return $results;
    }
}
