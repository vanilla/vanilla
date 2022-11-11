<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/tags` resource.
 */
class TagsApiController extends AbstractApiController
{
    /** @var TagModel */
    private $tagModel;

    /**
     * TagsApiController constructor.
     *
     * @param TagModel $tagModel
     */
    public function __construct(TagModel $tagModel)
    {
        $this->tagModel = $tagModel;
    }

    /**
     * Get a schema instance comprised of all available draft fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema()
    {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                "tagID:i?",
                "id:i?",
                "name:s",
                "type:s?",
                "urlcode:s?",
                "urlCode:s?",
                "parentTagID:i|null?",
                "countDiscussions:i",
            ]);
        }

        return $schema;
    }

    /**
     * Get tags from a query string.
     *
     * @param array $query
     * @return array
     */
    public function index(array $query)
    {
        $this->permission();
        $in = $this->schema([
            "query:s?",
            "type:a?" => [
                "items" => [
                    "type" => "string",
                ],
                "style" => "form",
            ],
            "parentID:a?" => [
                "items" => [
                    "type" => "integer",
                ],
                "style" => "form",
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of tags.",
                "minimum" => 1,
                "default" => \TagModel::LIMIT_DEFAULT,
            ],

            "sort:s?" => [
                "enum" => ApiUtils::sortEnum("countDiscussions", "tagID", "name"),
            ],
            "excludeNoCountDiscussion:b?" => [
                "description" => "Filter tags with no discussion counts",
                "default" => false,
            ],
        ]);

        if (key_exists("limit", $query)) {
            $page = $query["page"] ?? 1;
            [$options["offset"], $options["limit"]] = offsetLimit("p{$page}", $query["limit"]);
        }

        if (key_exists("sort", $query)) {
            $options["sort"] = $query["sort"];
        }

        $query = $in->validate($query);

        $query["type"] = $query["type"] ?? ["all"];
        $query["type"] = array_map(function ($type) {
            return $type === "User" ? "" : $type;
        }, $query["type"]);
        if (key_exists("excludeNoCountDiscussion", $query)) {
            $options["excludeNoCountDiscussion"] = $query["excludeNoCountDiscussion"];
        }
        $out = $this->schema([":a?" => $this->fullSchema()], "out");
        $options["extraFields"] = true;

        $tags = [];
        $searchTerm = $query["query"] ?? "";
        $tags = $this->tagModel->search($searchTerm, true, $query["parentID"] ?? [], $query["type"], $options);

        $allowedTypes = $query["type"] === ["tag"] ? [] : $query["type"];
        $tags = $this->normalizeTags($tags, $allowedTypes);
        $tags = $out->validate($tags);

        // urlCode was renamed to urlcode. For the sake of backwards-compatibility, temporarily kludge in the old casing.
        $tags = array_map(function ($tag) {
            $tag["urlCode"] = $tag["urlcode"] ?? "";
            return $tag;
        }, $tags);

        return $tags;
    }

    /**
     * Get a single tag by its ID.
     *
     * @param int $id
     * @return Data
     * @throws \Garden\Schema\ValidationException Validation Exception.
     * @throws \Garden\Web\Exception\HttpException Http Exception.
     * @throws NotFoundException Throws an exception if no tag is found.
     * @throws \Vanilla\Exception\PermissionException Throws an exception if the user doesn't have the Vanilla.Tagging.Add permission.
     */
    public function get(int $id): Data
    {
        $this->permission();
        $tag = $this->getTagFormattedForOutput($id);
        $result = new Data($tag);
        return $result;
    }

    /**
     * Post a new tag.
     *
     * @param array $body
     * @return Data
     * @throws \Garden\Schema\ValidationException Validation Exception.
     * @throws \Garden\Web\Exception\HttpException HttpException.
     * @throws NotFoundException Throws an exception if tag can't be found.
     * @throws \Vanilla\Exception\PermissionException Throws an exception if user doesn't have Garden.Community.Manage permission.
     */
    public function post(array $body): Data
    {
        $this->permission("Garden.Community.Manage");
        $in = $this->tagModel->getPostTagSchema();
        // A null type should be saved as an empty string in the DB.
        $body["type"] = $body["type"] ?? "";
        $validatedBody = $in->validate($body);

        // If we're specifying a type, make sure we're allowed to add tags to that type.
        if (isset($validatedBody["type"])) {
            $this->checkTypeAddSetting($validatedBody["type"]);
        }

        if (isset($validatedBody["parentTagID"])) {
            $this->parentExists($validatedBody["parentTagID"]);
        }

        // Create the slug. The tag model's save() method requires it.
        $validatedBody["urlcode"] = $validatedBody["urlcode"] ?? $this->tagModel->tagSlug($validatedBody["name"]);

        $normalizedBody = $this->tagModel->normalizeInput([$validatedBody])[0];

        // Don't allow overwriting existing tags.
        $duplicateTags = $this->tagModel->getWhere(["Name" => $normalizedBody["Name"]])->resultArray();
        if (!empty($duplicateTags)) {
            throw new ClientException("A tag with this name already exists.", 409);
        }

        $tagID = $this->tagModel->save($normalizedBody);
        if ($tagID) {
            $validatedTag = $this->getTagFormattedForOutput($tagID);
            $result = new Data($validatedTag);
            return $result;
        }
    }

    /**
     * Patch a tag via the API.
     *
     * @param int $id The tag ID.
     * @param array $body The tag fields.
     * @return Data
     * @throws \Garden\Schema\ValidationException Validation Exception.
     * @throws \Garden\Web\Exception\HttpException Http Exception.
     * @throws NotFoundException Throws exception if the tag to patch can't be found.
     * @throws \Vanilla\Exception\PermissionException Throws exception if the user doesn't have the Garden.Community.Manage permission.
     */
    public function patch(int $id, array $body): Data
    {
        $this->permission("Garden.Community.Manage");
        // A null type should be saved as an empty string in the DB.
        $body["type"] = $body["type"] ?? "";
        $in = $this->tagModel->getPatchTagSchema();
        $validatedBody = $in->validate($body, true);

        // If we're specifying a type, make sure we're allowed to add tags to that type.
        if (isset($validatedBody["type"])) {
            $this->checkTypeAddSetting($validatedBody["type"]);
        }

        if (isset($validatedBody["parentTagID"])) {
            $this->parentExists($validatedBody["parentTagID"]);
        }

        // Get the tag and throw a Not Found error if nothing comes back.
        $tags = $this->tagModel->getWhere(["TagID" => $id])->resultArray();
        if (empty($tags)) {
            throw new NotFoundException("Tag");
        } else {
            $tag = $tags[0];
        }

        // Add the urlcode and tagID to the body. The tag model's save() method needs it.
        $validatedBody["urlcode"] = $validatedBody["urlcode"] ?? $tag["Name"];
        $validatedBody["tagID"] = $id;

        $normalizedBody = $this->tagModel->normalizeInput([$validatedBody])[0];

        $tagID = $this->tagModel->save($normalizedBody);
        if ($tagID) {
            $validatedTag = $this->getTagFormattedForOutput($tagID);
            $result = new Data($validatedTag);
            return $result;
        }
    }

    /**
     * Delete a tag via the API.
     *
     * @param int $id The tag ID.
     * @throws ClientException Throws an exception if the tag is a parent.
     * @throws \Garden\Web\Exception\HttpException Http Exception.
     * @throws NotFoundException Throws an exception if the tag to delete isn't found.
     * @throws \Vanilla\Exception\PermissionException Throws exception if the user doesn't have the Garden.Community.Manage permission.
     */
    public function delete(int $id): void
    {
        $this->permission("Garden.Community.Manage");
        $tag = $this->tagModel->getWhere(["TagID" => $id])->FirstRow(DATASET_TYPE_ARRAY);

        if (empty($tag)) {
            throw new NotFoundException("Tag");
        } else {
            // Make sure the tag doesn't have any children or associated discussions.
            $isParent = $this->tagModel->getChildTags($id);
            if (!empty($isParent)) {
                throw new ClientException("You cannot delete tags that have associated child tags.", 409);
            } elseif ($tag["CountDiscussions"] > 0) {
                throw new ClientException("You cannot delete tags that have associated discussions.", 409);
            } else {
                $allowedTypes = Gdn::config("Tagging.Discussions.AllowedTypes", [""]);
                if (!in_array($tag["Type"] ?? "", $allowedTypes)) {
                    throw new ClientException("You cannot delete a reserved tag.", 409);
                }
                $this->tagModel->deleteID($id);
            }
        }
    }

    /**
     * Normalize Tag data for the api.
     *
     * @param array $tags
     * @param array $allowedTypes
     * @return array
     */
    private function normalizeTags(array &$tags, array $allowedTypes = []): array
    {
        foreach ($tags as $key => &$tag) {
            // we should remove tags that aren't explicitly whitelisted.
            // in-case they some how are returned by the search.
            $type = $tag["type"] ?? "";
            if (!in_array("all", $allowedTypes)) {
                if ($type !== "" && !in_array($type, $allowedTypes)) {
                    array_splice($tags, $key, 1);
                    continue;
                }
            }
            $tag["urlCode"] = $tag["urlcode"] = $tag["name"] ?? "";
            $tag["name"] = $tag["fullName"] ?? "";
            $tag["tagID"] = $tag["id"] ?? -1;
            $tag["type"] = stringIsNullOrEmpty($tag["type"]) ? "User" : $tag["type"];
        }
        return $tags;
    }

    /**
     * Takes a tagID and returns the normalized and validated tag data.
     *
     * @param int $tagID
     * @return array Returns the normalized and validated tag data.
     * @throws \Garden\Schema\ValidationException Throws a validation exception.
     * @throws NotFoundException Throws an exception if the tag isn't found.
     */
    private function getTagFormattedForOutput(int $tagID): array
    {
        $out = $this->tagModel->getFullTagSchema();
        $tagFromDB = $this->tagModel->getTagsByIDs([$tagID])[0];
        // Return type with the value of an empty string as null.
        $tagFromDB["Type"] = $tagFromDB["Type"] === "" ? null : $tagFromDB["Type"];
        $normalizedTag = $this->tagModel->normalizeOutput([$tagFromDB])[0];
        $validatedTag = $out->validate($normalizedTag);
        return $validatedTag;
    }

    /**
     * Check to see if you can add a tag of this specified type and throw an error if you can't.
     *
     * @param string $type
     * @throws ClientException Throws an error if you can't add tags of this type.
     */
    private function checkTypeAddSetting(string $type): void
    {
        // Get all the tag types.
        $allTypes = array_change_key_case($this->tagModel->getTagTypes(), 0);

        // Check to see if the type is an existing one, and if it isn't make sure you're allowed to add tags to it.
        if (in_array(strtolower($type), array_keys($allTypes)) && !$this->tagModel->canAddTagForType($type)) {
            throw new ClientException(sprintf("You cannot add tags with the type '%s'.", $type));
        }
    }

    /**
     * Checks to make sure the parent tag exists and throws an error if it doesn't.
     *
     * @param int $parentTagID The ID of the tag to check.
     * @throws NotFoundException Throws an exception if the parent tag isn't found.
     */
    private function parentExists(int $parentTagID): void
    {
        $parentExists = $this->tagModel->getID($parentTagID);
        if (!$parentExists) {
            throw new ClientException("Parent tag not found.");
        }
    }
}
