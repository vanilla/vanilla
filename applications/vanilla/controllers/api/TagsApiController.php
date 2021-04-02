<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;

/**
 * API Controller for the `/tags` resource.
 */
class TagsApiController extends AbstractApiController {


    /** @var TagModel */
    private $tagModel;

    /**
     * TagsApiController constructor.
     *
     * @param TagModel $tagModel
     */
    public function __construct(TagModel $tagModel) {
        $this->tagModel = $tagModel;
    }

    /**
     * Get a schema instance comprised of all available draft fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'tagID:i?',
                'id:i?',
                'name:s',
                'urlcode:s?',
                'urlCode:s?',
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
    public function index(array $query) {
        $this->permission();
        $in = $this->schema(
            [
                'query:s?',
                "type:s" => ["default" => "default"],
                "parentID:i?",
            ]
        );

        $query = $in->validate($query);

        $out = $this->schema([':a?' => $this->fullSchema()], 'out');
        $options['extraFields'] = true;

        $tags = [];
        $searchTerm = $query['query'] ?? '';
        if ($searchTerm) {
            $tags = $this->tagModel->search(
                $searchTerm,
                true,
                $query["parentID"] ?? false,
                $query["type"],
                $options
            );
        }

        $allowedTypes = $query["type"] === "default" ? [] : [$query["type"]];
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
     * @throws \Garden\Web\Exception\NotFoundException Throws an exception if no tag is found.
     * @throws \Vanilla\Exception\PermissionException Throws an exception if the user doesn't have the Vanilla.Tagging.Add permission.
     */
    public function get(int $id): Data {
        $this->permission('Vanilla.Tagging.Add');
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
     * @throws \Garden\Web\Exception\NotFoundException Throws an exception if tag can't be found.
     * @throws \Vanilla\Exception\PermissionException Throws an exception if user doesn't have Garden.Community.Manage permission.
     */
    public function post(array $body): Data {
        $this->permission('Garden.Community.Manage');
        $in = $this->tagModel->getPostTagSchema();
        $validatedBody = $in->validate($body);

        // Create the slug. The tag model's save() method requires it.
        $validatedBody['urlcode'] = $validatedBody['urlcode'] ?? $this->tagModel->tagSlug($validatedBody['name']);

        $normalizedBody = $this->tagModel->normalizeInput([$validatedBody])[0];

        // Don't allow overwriting existing tags.
        $duplicateTags = $this->tagModel->getWhere(['Name' => $normalizedBody['Name']])->resultArray();
        if (!empty($duplicateTags)) {
            throw new \Garden\Web\Exception\ClientException('A tag with this name already exists.', 409);
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
     * @throws \Garden\Web\Exception\NotFoundException Throws exception if the tag to patch can't be found.
     * @throws \Vanilla\Exception\PermissionException Throws exception if the user doesn't have the Garden.Community.Manage permission.
     */
    public function patch(int $id, array $body): Data {
        $this->permission('Garden.Community.Manage');
        $in = $this->tagModel->getPatchTagSchema();
        $validatedBody = $in->validate($body, true);

        // Get the tag and throw a Not Found error if nothing comes back.
        $tags = $this->tagModel->getWhere(['TagID' => $id])->resultArray();
        if (empty($tags)) {
            throw new \Garden\Web\Exception\NotFoundException('Tag');
        } else {
            $tag = $tags[0];
        }

        // Add the urlcode and tagID to the body. The tag model's save() method needs it.
        $validatedBody['urlcode'] = $validatedBody['urlcode'] ?? $tag['Name'];
        $validatedBody['tagID'] = $id;

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
     * @throws \Garden\Web\Exception\ClientException Throws an exception if the tag is a parent.
     * @throws \Garden\Web\Exception\HttpException Http Exception.
     * @throws \Garden\Web\Exception\NotFoundException Throws an exception if the tag to delete isn't found.
     * @throws \Vanilla\Exception\PermissionException Throws exception if the user doesn't have the Garden.Community.Manage permission.
     */
    public function delete(int $id): void {
        $this->permission('Garden.Community.Manage');
        $tags = $this->tagModel->getWhere(['TagID' => $id])->resultArray();
        if (empty($tags)) {
            throw new \Garden\Web\Exception\NotFoundException('Tag');
        } else {
            $tag = $tags[0];
            // Do we need to do something if the tag we're deleting is the Parent tag of some other tags?
            $isParent = $this->tagModel->getChildTags($id);
            if (!empty($isParent)) {
                throw new \Garden\Web\Exception\ClientException('You cannot delete tags that have associated child tags.', 409);
            } elseif ($tag['CountDiscussions'] > 0) {
                throw new \Garden\Web\Exception\ClientException('You cannot delete tags that have associated discussions.', 409);
            } else {
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
    private function normalizeTags(array &$tags, array $allowedTypes = []): array {
        foreach ($tags as $key => &$tag) {
            // we should remove tags that aren't explicitly whitelisted.
            // in-case they some how are returned by the search.
            $type = $tag['type'] ?? '';
            if ($type !== '' && !in_array($type, $allowedTypes)) {
                array_splice($tags, $key, 1);
                continue;
            }
            $tag['urlCode'] = $tag['urlcode'] = $tag['name'] ?? "";
            $tag['name'] = $tag['fullName'] ?? "";
            $tag["tagID"] = $tag["id"] ?? -1;
        }
        return $tags;
    }

    /**
     * Takes a tagID and returns the normalized and validated tag data.
     *
     * @param int $tagID
     * @return array Returns the normalized and validated tag data.
     * @throws \Garden\Schema\ValidationException Throws a validation exception.
     * @throws \Garden\Web\Exception\NotFoundException Throws an exception if the tag isn't found.
     */
    private function getTagFormattedForOutput(int $tagID): array {
        $out = $this->tagModel->getFullTagSchema();
        $tagFromDB = $this->tagModel->getTagsByIDs([$tagID]);
        $normalizedTag = $this->tagModel->normalizeOutput($tagFromDB)[0];
        $validatedTag = $out->validate($normalizedTag);
        return $validatedTag;
    }
}
