<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Tagging
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Community\Events\DiscussionTagEvent;
use Vanilla\Models\ModelCache;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Tagging plugin.
 */
class TagModel extends Gdn_Model
{
    const IX_EXTENDED = "x";

    const IX_TAGID = "id";

    protected $Types;

    protected static $instance;

    public $StringTags;

    const FIELD_MAPPINGS = ["urlcode" => "Name", "name" => "FullName"];

    const LIMIT_DEFAULT = 20;

    /** @var ModelCache */
    private $modelCache;

    /**
     * @param string $name
     */
    public function __construct($name = "")
    {
        parent::__construct("Tag");
        $this->StringTags = c("Vanilla.Tagging.StringTags");
        $this->modelCache = new ModelCache("tags", Gdn::cache());
    }

    /**
     * @return void
     */
    public function invalidateCache()
    {
        $this->modelCache->invalidateAll();
    }

    /**
     * Clear cache when a tag is updated.
     */
    protected function onUpdate()
    {
        parent::onUpdate();
        $this->invalidateCache();
    }

    /**
     * The singleton instance of this object.
     * @return TagModel
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TagModel();
        }
        return self::$instance;
    }

    /**
     * Create a DiscussionTagEvent from the event Tag data.
     *
     * @param array $discussion to which tags were added.
     * @param array $tagIDs An array of tag IDs.
     * @return DiscussionTagEvent
     */
    protected function createDiscussionTagEvent(array $discussion, array $tagIDs): DiscussionTagEvent
    {
        $senderField = $discussion["UpdateUserID"] ?? $discussion["InsertUserID"];
        $sender = $senderField ? Gdn::userModel()->getFragmentByID($senderField) : null;
        $discussionEvent = DiscussionModel::instance()->eventFromRow(
            $discussion,
            DiscussionTagEvent::ACTION_DISCUSSION_TAGGED,
            $sender
        );
        // Obtain tags data
        $tagsData = $this->getWhere(["TagID" => $tagIDs])->resultArray();

        return new DiscussionTagEvent($discussionEvent, $tagsData);
    }

    /**
     *
     *
     * @return array
     */
    public function defaultTypes()
    {
        $types = array_filter($this->types(), function ($val) {
            if (val("default", $val)) {
                return true;
            }
            return false;
        });
        return $types;
    }

    /**
     *
     *
     * @param array $formPostValues
     * @param bool $settings
     * @return bool|int
     * @throws Exception
     */
    public function save($formPostValues, $settings = false)
    {
        // Get the ID of an existing tag with the same name.
        $existingTag = $this->getWhere([
            "Name" => $formPostValues["Name"],
            "TagID <>" => val("TagID", $formPostValues),
        ])->firstRow(DATASET_TYPE_ARRAY);
        if ($existingTag) {
            if (!val("TagID", $formPostValues)) {
                return $existingTag["TagID"];
            }

            // This tag will be merged with the existing one.
            $px = $this->Database->DatabasePrefix;
            $fromID = $formPostValues["TagID"];
            $toID = $existingTag["TagID"];

            try {
                $this->Database->beginTransaction();

                // Delete every overlapping tags.
                $sql = "delete tg.*
               from {$px}TagDiscussion tg
               join {$px}TagDiscussion tg2
                 on tg.DiscussionID = tg2.DiscussionID
                   and tg.TagID = :FromID and tg2.TagID = :ToID";
                $this->Database->query($sql, [":FromID" => $fromID, ":ToID" => $toID]);

                // Update the tagged discussions.
                $sql = "update {$px}TagDiscussion
               set TagID = :ToID
               where TagID = :FromID";
                $this->Database->query($sql, [":FromID" => $fromID, ":ToID" => $toID]);

                // Update the counts
                $this->updateTagCountDiscussions($toID);

                // Delete the old tag.
                $sql = "delete from {$px}Tag where TagID = :FromID";
                $this->Database->query($sql, [":FromID" => $fromID]);

                $this->Database->commitTransaction();
            } catch (Exception $ex) {
                $this->Database->rollbackTransaction();
                throw $ex;
            }

            return $toID;
        } else {
            if (Gdn::session()->checkPermission("Vanilla.Tagging.Add")) {
                // Tag-type tags (i.e., user generated tags) are saved with no type.
                if (strtolower(val("Type", $formPostValues)) == "tag") {
                    $formPostValues["Type"] = "";
                }
                return parent::save($formPostValues, $settings);
            } else {
                return false;
            }
        }
    }

    /**
     * Add a tag type.
     *
     * @param string $key
     * @param array $row
     */
    public function addType($key, $row)
    {
        $row["key"] = $key;
        $this->Types[$key] = $row;
    }

    /**
     * Get the available tag types.
     */
    public function types()
    {
        if (!isset($this->Types)) {
            $this->Types = [
                "" => [
                    "key" => "",
                    "name" => "All",
                    "plural" => "All",
                    "default" => true,
                    "addtag" => true,
                ],
                "tags" => [
                    "key" => "tags",
                    "name" => "Tag",
                    "plural" => "Tags",
                    "default" => false,
                    "addtag" => true,
                ],
            ];

            $this->fireEvent("Types");
        }

        return $this->Types;
    }

    /**
     * Unset tag types.
     */
    public function resetTypes()
    {
        $this->Types = null;
    }

    /**
     *
     *
     * @return array
     */
    public function getTagTypes()
    {
        $tagTypes = $this->types();

        if (!is_array($tagTypes)) {
            $tagTypes = [];
        }

        // Remove the defaults out of the list, and add them to the start
        $start = array_intersect_key($tagTypes, ["" => [], "tags" => []]);
        unset($tagTypes[""]);
        unset($tagTypes["tags"]);

        // Sort by keys, and because the default, "Tags," has a blank key, it
        // will be set as the first key, which is good for the tabs.
        if (count($tagTypes)) {
            ksort($tagTypes);
        }

        $tagTypes = array_merge($start, $tagTypes);

        return $tagTypes;
    }

    /**
     * Update the tag count per discussion in the Tag table
     *
     * @param int $tagID
     */
    public function updateTagCountDiscussions($tagID)
    {
        $px = $this->Database->DatabasePrefix;
        // Update the counts.
        $sql = "update {$px}Tag t
         set CountDiscussions = (
            select count(DiscussionID)
            from {$px}TagDiscussion td
            where td.TagID = t.TagID)
          where t.TagID = :TagID";
        $this->Database->query($sql, [":TagID" => $tagID]);
    }

    /**
     * Get all of the tags related to the current tag.
     *
     * @param mixed $tag
     */
    public function getRelatedTags($tag)
    {
        if (is_numeric($tag)) {
            $tag = $this->getID($tag, DATASET_TYPE_ARRAY);
        }
        if (!is_array($tag)) {
            return [];
        }

        $result = [
            $tag["Type"] => [$tag],
        ];

        // Get all of the parent tags.
        for (
            $i = 0, $parentid = val("ParentTagID", $tag);
            $parentid && $i < 10;
            $i++, $parentid = val("ParentTagID", $tag)
        ) {
            $tag = $this->getID($parentid, DATASET_TYPE_ARRAY);
            if (!$tag) {
                break;
            }

            $result[$tag["Type"]][] = $tag;
        }
        return $result;
    }

    /**
     * Get the child tags associated with the parent tag id.
     *
     * @param int $parentTagID The parent tag ID to check for children.
     * @return array All child tag rows
     */
    public function getChildTags($parentTagID)
    {
        $childTags = $this->getWhere(["ParentTagID" => $parentTagID])->resultArray();
        if (!count(array_filter($childTags))) {
            $childTags = [];
        }

        return $childTags;
    }

    /**
     * Get detailed tag data for a given discussion. An example use case would
     * be when editing discussions: any non-typical tags, that is, ones that
     * may appear to be categories, should have their specific data available,
     * like Type, or Source.
     *
     * @param int $discussionID
     * @return array
     */
    public function getDiscussionTags($discussionID, $indexed = true)
    {
        $tags = Gdn::sql()
            ->select("t.*")
            ->from("TagDiscussion td")
            ->join("Tag t", "td.TagID = t.TagID")
            ->where("td.DiscussionID", $discussionID)
            ->get()
            ->resultArray();

        if ($indexed) {
            if ($indexed === TagModel::IX_TAGID) {
                $tags = Gdn_DataSet::index($tags, "TagID");
            } else {
                // The tags are indexed by type.
                $tags = Gdn_DataSet::index($tags, "Type", ["Unique" => false]);
                if ($indexed === TagModel::IX_EXTENDED) {
                    // The tags are indexed by type, but tags with no type are seperated.
                    if (array_key_exists("", $tags)) {
                        $tags = ["Tags" => $tags[""], "XTags" => $tags];
                        unset($tags["XTags"][""]);
                    } else {
                        $tags = ["Tags" => [], "XTags" => $tags];
                    }
                }
            }
        }

        return $tags;
    }

    /**
     * Join the tags to a set of discussions.
     *
     * @param $data
     */
    public function joinTags(&$data)
    {
        // If we're dealing with an instance of Gdn_Dataset, grab a reference to its results.
        if ($data instanceof Gdn_DataSet) {
            $rows = $data->result();
        } else {
            $rows = &$data;
        }

        $ids = [];
        foreach ($rows as $row) {
            $discussionId = val("DiscussionID", $row, null) ?? val("discussionID", $row);
            if ($discussionId) {
                $ids[] = $discussionId;
            }
        }

        // Select the tags.
        $all_tags = $this->SQL
            ->select("td.DiscussionID, t.TagID, t.Name, t.FullName")
            ->from("TagDiscussion td")
            ->join("Tag t", "t.TagID = td.TagID")
            ->whereIn("td.DiscussionID", $ids)
            ->get()
            ->resultArray();

        $all_tags = Gdn_DataSet::index($all_tags, "DiscussionID", ["Unique" => false]);

        foreach ($rows as &$row) {
            $discussionId = val("DiscussionID", $row, null) ?? val("discussionID", $row);
            if (isset($all_tags[$discussionId])) {
                $tags = $all_tags[$discussionId];

                if ($this->StringTags) {
                    $tags = array_column($tags, "Name");
                    setValue("Tags", $row, implode(",", $tags));
                } else {
                    foreach ($tags as &$trow) {
                        unset($trow["DiscussionID"]);
                    }
                    setValue("Tags", $row, $tags);
                }
            } else {
                if ($this->StringTags) {
                    setValue("Tags", $row, "");
                } else {
                    setValue("Tags", $row, []);
                }
            }
        }
    }

    /**
     * Get the TagFragment Schema.
     *
     * @returns Schema
     */
    public function getTagFragmentSchema(): Schema
    {
        $schema = Schema::parse(["tagID:i", "name:s", "urlcode:s?"]);
        return $schema;
    }

    /**
     * Get the schema for posting a tag via the API.
     *
     * @return Schema
     */
    public function getPostTagSchema(): Schema
    {
        $schema = Schema::parse(["name:s", "urlcode:s?", "parentTagID:i|n?", "type:s|n?"]);
        return $schema;
    }

    /**
     * Get the schema for patching a tag via the API.
     */
    public function getPatchTagSchema(): Schema
    {
        $postSchema = $this->getPostTagSchema();
        $patchSchema = $postSchema->merge(Schema::parse(["name:s?"]));
        return $patchSchema;
    }

    /**
     * Get the full tag schema.
     *
     * @return Schema
     */
    public function getFullTagSchema(): Schema
    {
        $fragmentSchema = $this->getTagFragmentSchema();
        $fullSchema = $fragmentSchema->merge(
            Schema::parse([
                "urlcode:s",
                "parentTagID:i?",
                "type:s?",
                "insertUserID:i",
                "dateInserted:dt",
                "countDiscussions:i",
            ])
        );
        return $fullSchema;
    }

    /**
     * Get a tag fragment schema.
     *
     * @return Schema
     */
    public function tagFragmentSchema(): Schema
    {
        $schema = Schema::parse(["tagID:i", "name:s", "urcode:s?"]);
        return $schema;
    }

    /**
     * Get the schema to add tags to a discussion via the API.
     *
     * @returns Schema
     */
    public function getAddTagSchema(): Schema
    {
        $schema = Schema::parse([
            "tagIDs:a?" => ["items" => ["type" => "integer"]],
            "urlcodes:a?" => ["items" => ["type" => "string"]],
        ]);
        return $schema;
    }

    /**
     * Validate a set of tags to add or set on a discussion (sent as the body from the "/discussions/{id}/tags" endpoint).
     *
     * @param array $tagSet The set of tags to check against the AddTagSchema.
     * @return array Returns the validated tag set.
     * @throws ClientException Throws an exception if an invalid field is given.
     * @throws \Garden\Schema\ValidationException Throws an error if invalid.
     */
    public function validateTagReference(array $tagSet): array
    {
        $in = $this->getAddTagSchema();
        $schemaProperties = array_keys($in->getSchemaArray()["properties"]);
        foreach ($tagSet as $field => $value) {
            if (!in_array($field, $schemaProperties)) {
                throw new ClientException(
                    "{$field} is not a valid field. Fields must be one of: " . implode(", ", $schemaProperties) . "."
                );
            }
        }
        $validatedTagSet = $in->validate($tagSet);
        return $validatedTagSet;
    }

    /**
     * Validates a set of tags to send back as tag fragments.
     *
     * @param array $tags The set of tags to validate.
     * @param Schema|null $out
     * @return array Returns the validated tag set.
     */
    public function validateTagFragmentsOutput(array $tags, $out = null): array
    {
        if (!($out instanceof Schema)) {
            $out = $this->getTagFragmentSchema();
        }
        $validatedTags = [];
        foreach ($tags as $tag) {
            $validatedTags[] = $out->validate($tag);
        }
        return $validatedTags;
    }

    /**
     * Takes a set of tagIDs and/or urlcodes and sends back an array of tags.
     *
     * @param array $tagReference the set of tagIDs and/or urlcodes (in the form of ["tagIDs" => [tagIDs], "urlcodes" => [urlcodes]]).
     * @return array Returns an array of tags.
     * @throws NotFoundException Throws an exception if a tag isn't found.
     */
    public function getTagsFromReferences(array $tagReference): array
    {
        $codes = [];
        $ids = [];
        foreach ($tagReference as $field => $value) {
            if ($field === "urlcodes") {
                $codes = $value;
            } else {
                $ids = $value;
            }
        }
        $tags = empty($codes) ? [] : $this->getTagsByUrlCodes($codes);
        $tags = empty($ids) ? $tags : array_merge($tags, $this->getTagsByIDs($ids));
        return $tags;
    }

    /**
     * Normalize tag input.
     *
     * @param array $tags An array of tags to normalize.
     * @return array
     */
    public function normalizeInput(array $tags): array
    {
        $normalizedTags = [];

        foreach ($tags as $tag) {
            $normalizedTags[] = \Vanilla\Models\LegacyModelUtils::normalizeApiInput($tag, self::FIELD_MAPPINGS);
        }

        return $normalizedTags;
    }

    /**
     * Normalize tag output.
     *
     * @param array $tags An array of tags to normalize.
     * @return array
     */
    public function normalizeOutput(array $tags): array
    {
        $normalizedTags = [];

        foreach ($tags as $tag) {
            $normalized = \Vanilla\Models\LegacyModelUtils::normalizeApiOutput($tag, self::FIELD_MAPPINGS);
            if (empty($normalized["name"])) {
                $normalized["name"] = t("(Untitled)");
            }
            $normalizedTags[] = $normalized;
        }

        return $normalizedTags;
    }

    /**
     * Get tags given an array of url codes.
     *
     * @param array $codes An array of url codes (corresponds to the "Name" column in the Tag table).
     * @param bool $throw Whether to throw an error if any codes aren't found.
     * @return array Returns an array of rows from the database.
     * @throws NotFoundException Throws an exception if any tags aren't found and $throw === true.
     */
    public function getTagsByUrlCodes(array $codes, bool $throw = true): array
    {
        $tags = $this->SQL
            ->select()
            ->from("Tag")
            ->where("Name", $codes)
            ->get()
            ->resultArray();
        if (!$throw || count($codes) === count($tags)) {
            return $tags;
        } else {
            $tagNames = array_column($tags, "Name");
            $missing = array_diff($codes, $tagNames);
            $missingTags = ["Urlcodes" => []];
            foreach ($missing as $field => $value) {
                array_push($missingTags["Urlcodes"], $value);
            }
            throw new NotFoundException("Tag(s)", $missingTags);
        }
    }

    /**
     * Get tags given an array of tag IDs.
     *
     * @param array $ids An array of url codes (corresponds to the "Name" column in the Tag table).
     * @param bool $throw Whether to throw an error if any codes aren't found.
     * @return array Returns an array of rows from the database.
     * @throws NotFoundException Throws an exception if any tags aren't found and $throw === true.
     */
    public function getTagsByIDs(array $ids, bool $throw = true): array
    {
        $tags = $this->SQL
            ->select()
            ->from("Tag")
            ->where("TagID", $ids)
            ->get()
            ->resultArray();
        if (!$throw || count($ids) === count($tags)) {
            return $tags;
        } else {
            $tagIds = array_column($tags, "TagID");
            $missingTags = ["TagIds" => array_diff($ids, $tagIds)];
            throw new NotFoundException("Tag(s)", $missingTags);
        }
    }

    /**
     * Add existing tags to a discussion.
     *
     * @param int $discussionID The ID of the discussion to add the tags to.
     * @param array $tags An array of tag IDs.
     * @param bool $bypassCheckMaxTagsLimit Do we bypass the MaxTagsLimit check?
     * @return void
     * @throws Gdn_UserException Throws an exception if some of the tags don't exist.
     */
    public function addDiscussion($discussionID, $tags, $bypassCheckMaxTagsLimit = false): void
    {
        $tagIDs = array_unique($tags);

        $validTags = $this->SQL
            ->select("*")
            ->from("Tag")
            ->where("TagID", $tagIDs)
            ->get()
            ->resultArray();

        if (count($tagIDs) != count($validTags)) {
            throw new Gdn_UserException("Non existing tag(s) supplied.");
        }

        $tagsToAdd = $tagIDs;

        $currentTags = $this->getDiscussionTags($discussionID, TagModel::IX_TAGID);
        if ($currentTags) {
            $tagsToAdd = array_diff($tagsToAdd, array_keys($currentTags));
        }

        // Do we want to enforce the maximum limit of tags allowed?
        if (!$bypassCheckMaxTagsLimit) {
            $this->checkMaxTagsLimit(array_merge($tagsToAdd, $currentTags));
        }

        if (!empty($tagsToAdd)) {
            $now = Gdn_Format::toDateTime();

            // Insert new tags
            foreach ($tagsToAdd as $key => $tagID) {
                $categoryID = $validTags[$tagID]["CategoryID"] ?? $validTags[$key]["CategoryID"];
                $this->SQL->options("Ignore", true)->insert("TagDiscussion", [
                    "DiscussionID" => $discussionID,
                    "TagID" => $tagID,
                    "DateInserted" => $now,
                    "CategoryID" => $categoryID,
                ]);
            }
            // Dispatch a DiscussionTag event
            $discussion = DiscussionModel::instance()->getID($discussionID, DATASET_TYPE_ARRAY);
            if ($discussion) {
                $discussionTagEvent = $this->createDiscussionTagEvent($discussion, $tagsToAdd);
                $this->getEventManager()->dispatch($discussionTagEvent);
            }

            // Increment the tag counts.
            $this->SQL
                ->update("Tag")
                ->set("CountDiscussions", "CountDiscussions + 1", false)
                ->whereIn("TagID", $tagsToAdd)
                ->put();
        }
    }

    /**
     *
     *
     * @param $discussion_id
     * @param $tags
     * @param array $types
     * @param int $category_id
     * @param string $new_type
     * @throws Exception
     */
    public function saveDiscussion($discussion_id, $tags, $types = [""], $category_id = 0, $new_type = "")
    {
        // Make sure we're not adding more than the allowed number of tags.
        $this->checkMaxTagsLimit($tags);

        // First grab the current tags.
        $all_tags = $current_tags = $this->getDiscussionTags($discussion_id, TagModel::IX_TAGID);

        // Put all the default tag types in the types if necessary.
        if (in_array("", $types)) {
            $types = array_merge($types, array_keys($this->defaultTypes()));
            $types = array_unique($types);
        }

        // Remove the types from the current tags that we don't need anymore.
        $current_tags = array_filter($current_tags, function ($row) use ($types) {
            if (in_array($row["Type"], $types)) {
                return true;
            }
            return false;
        });

        // Turn the tags into a nice array.
        if (is_string($tags)) {
            $tags = TagModel::splitTags($tags);
        }

        $new_tags = [];
        $tag_ids = [];

        // See which tags are new and which ones aren't.
        foreach ($tags as $tag_id) {
            if (is_id($tag_id)) {
                $tag_ids[$tag_id] = true;
            } else {
                $new_tags[TagModel::tagSlug($tag_id)] = $tag_id;
            }
        }

        // See if any of the new tags actually exist by searching by name.
        if (!empty($new_tags)) {
            $found_tags = $this->getWhere(["Name" => array_keys($new_tags)])->resultArray();
            foreach ($found_tags as $found_tag_row) {
                $tag_ids[$found_tag_row["TagID"]] = $found_tag_row;
                unset($new_tags[TagModel::tagSlug($found_tag_row["Name"])]);
            }
        }

        // Add any remaining tags that need to be added.
        if (Gdn::session()->checkPermission("Vanilla.Tagging.Add")) {
            foreach ($new_tags as $name => $full_name) {
                $new_tag = [
                    "Name" => trim(str_replace(" ", "-", strtolower($name)), "-"),
                    "FullName" => $full_name,
                    "Type" => $new_type,
                    "CategoryID" => $category_id,
                    "InsertUserID" => Gdn::session()->UserID,
                    "DateInserted" => Gdn_Format::toDateTime(),
                    "CountDiscussions" => 0,
                ];
                $tag_id = $this->SQL->options("Ignore", true)->insert("Tag", $new_tag);
                $this->invalidateCache();
                $tag_ids[$tag_id] = true;
            }
        }

        // Grab the tags so we can see more information about them.
        $save_tags = $this->getWhere(["TagID" => array_keys($tag_ids)])->resultArray();
        // Add any parent tags that may need to be added.
        foreach ($save_tags as $save_tag) {
            $parent_tag_id = val("ParentTagID", $save_tag);
            if ($parent_tag_id) {
                $tag_ids[$parent_tag_id] = true;
            }
            $all_tags[$save_tag["TagID"]] = $save_tag;
        }

        // Remove tags that are already associated with the discussion.
        //      $same_tag_ids = array_intersect_key($tag_ids, $current_tags);
        //      $current_tags = array_diff_key($current_tags, $same_tag_ids);
        //      $tag_ids = array_diff_key($tag_ids, $same_tag_ids);

        // Figure out the tags we need to add.
        $insert_tag_ids = array_diff_key($tag_ids, $current_tags);
        // Figure out the tags we need to remove.
        $delete_tag_ids = array_diff_key($current_tags, $tag_ids);

        $now = Gdn_Format::toDateTime();

        if (count($insert_tag_ids) > 0) {
            // Insert the new tag mappings.
            foreach ($insert_tag_ids as $tag_id => $bool) {
                if (isset($all_tags[$tag_id])) {
                    $insert_category_id = $all_tags[$tag_id]["CategoryID"];
                } else {
                    $insert_category_id = $category_id;
                }

                $this->SQL->options("Ignore", true)->insert("TagDiscussion", [
                    "DiscussionID" => $discussion_id,
                    "TagID" => $tag_id,
                    "DateInserted" => $now,
                    "CategoryID" => $insert_category_id,
                ]);
            }

            $discussion = DiscussionModel::instance()->getID($discussion_id, DATASET_TYPE_ARRAY);
            if ($discussion) {
                // Dispatch a DiscussionTag event
                $discussionTagEvent = $this->createDiscussionTagEvent($discussion, array_keys($insert_tag_ids));
                $this->getEventManager()->dispatch($discussionTagEvent);
            }
        }

        // Delete the old tag mappings.
        if (!empty($delete_tag_ids)) {
            $this->SQL->delete("TagDiscussion", [
                "DiscussionID" => $discussion_id,
                "TagID" => array_keys($delete_tag_ids),
            ]);
        }

        // Increment the tag counts.
        if (!empty($insert_tag_ids)) {
            $this->SQL
                ->update("Tag")
                ->set("CountDiscussions", "CountDiscussions + 1", false)
                ->whereIn("TagID", array_keys($insert_tag_ids))
                ->put();
        }

        // Decrement the tag counts.
        if (!empty($delete_tag_ids)) {
            $this->SQL
                ->update("Tag")
                ->set("CountDiscussions", "CountDiscussions - 1", false)
                ->whereIn("TagID", array_keys($delete_tag_ids))
                ->put();
        }
    }

    /**
     * Get discussions by tag(s)
     *
     * @param string|array $tag tag name(s)
     * @param int $limit limit number of result
     * @param int $offset start result at this offset
     * @param string $sortField column to sort results by
     * @param string $sortDirection the direction to sort the discussions
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getDiscussions($tag, $limit, $offset, $sortField = "d.DateLastComment", $sortDirection = "desc")
    {
        if (!is_array($tag)) {
            $tags = array_map("trim", explode(",", $tag));
        }

        $taggedDiscussionIDs = $this->SQL
            ->select("td.DiscussionID")
            ->from("TagDiscussion td")
            ->join("Tag t", "t.TagID = td.TagID")
            ->join("Discussion d", "d.DiscussionID = td.DiscussionID")
            ->whereIn("t.Name", $tags)
            ->limit($limit, $offset)
            ->orderBy($sortField, $sortDirection)
            ->get()
            ->resultArray();

        $taggedDiscussionIDs = array_column($taggedDiscussionIDs, "DiscussionID");

        $discussionModel = new DiscussionModel();
        $discussions = $discussionModel->getWhere([
            "Announce" => "all",
            "d.DiscussionID" => $taggedDiscussionIDs,
        ]);

        return $discussions;
    }

    /**
     * Get tagIDs for given tag names.
     *
     * @param string[] $names Tag names.
     *
     *@return int[]
     */
    public function getTagIDsByName(array $names): array
    {
        if (empty($names)) {
            return [];
        }
        $ids = $this->modelCache->getCachedOrHydrate([__METHOD__, $names], function () use ($names) {
            $result = $this->SQL
                ->select(["TagID", "Name"])
                ->from("Tag")
                ->where("Name", $names)
                ->get()
                ->resultArray();
            $ids = array_column($result, "TagID", "Name");
            return $ids;
        });
        return $ids;
    }

    /**
     * Get a tagID by its a name.
     *
     * @param string $name
     *
     * @return int|null
     */
    public function getTagIDByName(string $name): ?int
    {
        $tagIDs = $this->getTagIDsByName([$name]);

        return $tagIDs[$name] ?? null;
    }

    /**
     * Deprecated sql generation.
     *
     * @param Gdn_SQLDriver $sql
     * @param mixed $tag
     * @param int $limit
     * @param int $offset
     * @param string $op
     * @deprecated
     */
    public function setTagSql($sql, $tag, &$limit, &$offset = 0, $op = "or")
    {
        deprecated("TagModel->setTagSql()", "TagModel->getDiscussions()", "2018-06-19");
        $sortField = "d.DateLastComment";
        $sortDirection = "desc";
        $tagSql = clone Gdn::sql();
        if ($dateFrom = Gdn::request()->get("DateFrom")) {
            // Find the discussion ID of the first discussion created on or after the date from.
            $discussionIDFrom = $tagSql
                ->getWhere("Discussion", ["DateInserted >= " => $dateFrom], "DiscussionID", "asc", 1)
                ->value("DiscussionID");
            $sortField = "d.DiscussionID";
        }
        if (!is_array($tag)) {
            $tags = array_map("trim", explode(",", $tag));
        }
        $tagIDs = $tagSql
            ->select("TagID")
            ->from("Tag")
            ->whereIn("Name", $tags)
            ->get()
            ->resultArray();
        $tagIDs = array_column($tagIDs, "TagID");
        if ($op == "and" && count($tags) > 1) {
            $discussionIDs = $tagSql
                ->select("DiscussionID")
                ->select("TagID", "count", "CountTags")
                ->from("TagDiscussion")
                ->whereIn("TagID", $tagIDs)
                ->groupBy("DiscussionID")
                ->having("CountTags >=", count($tags))
                ->limit($limit, $offset)
                ->orderBy("DiscussionID", "desc")
                ->get()
                ->resultArray();
            $limit = "";
            $offset = 0;
            $discussionIDs = array_column($discussionIDs, "DiscussionID");
            $sql->whereIn("d.DiscussionID", $discussionIDs);
            $sortField = "d.DiscussionID";
        } else {
            $sql->join("TagDiscussion td", "d.DiscussionID = td.DiscussionID")
                ->limit($limit, $offset)
                ->whereIn("td.TagID", $tagIDs);
            if ($op == "and") {
                $sortField = "d.DiscussionID";
            }
        }
        // Set up the sort field and direction.
        saveToConfig(
            [
                "Vanilla.Discussions.SortField" => $sortField,
                "Vanilla.Discussions.SortDirection" => $sortDirection,
            ],
            "",
            false
        );
    }

    /**
     * Unpivot tags that are grouped by type.
     *
     * @param array $tags
     * @return array
     */
    public function unpivot($tags)
    {
        $result = [];
        foreach ($tags as $rows) {
            $result = array_merge($result, $rows);
        }
        return $result;
    }

    /**
     *
     *
     * @param $column
     * @param null $userID
     * @return array
     * @throws Gdn_UserException
     */
    public function counts($column, $userID = null)
    {
        // Delete all the orphaned tagdiscussion records
        $px = $this->Database->DatabasePrefix;
        $sql = "delete td.* from {$px}TagDiscussion as td left join {$px}Discussion as d ON td.DiscussionID = d.DiscussionID where d.DiscussionID is null";
        $this->Database->query($sql);

        $result = ["Complete" => true];
        switch ($column) {
            case "CountDiscussions":
                Gdn::database()->query(
                    DBAModel::getCountSQL(
                        "count",
                        "Tag",
                        "TagDiscussion",
                        "CountDiscussions",
                        "DiscussionID",
                        "TagID",
                        "TagID"
                    )
                );
                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }
        return $result;
    }

    /**
     * Expand Tags.
     *
     * @param array $rows
     */
    public function expandTags(array &$rows): void
    {
        if (count($rows) === 0) {
            return;
        }
        $isSingle = ArrayUtils::isAssociative($rows);

        $tagSchema = $this->tagFragmentSchema();
        $populate = function (array &$rows) use ($tagSchema) {
            $this->joinTags($rows);
            foreach ($rows as &$row) {
                $row["Tags"] = $this->normalizeOutput($row["Tags"]);
                $row = ApiUtils::convertOutputKeys($row);
                unset($row["Tags"]);
                $this->validateTagFragmentsOutput($row["tags"], $tagSchema);
            }
        };

        if ($isSingle) {
            $rowsToPopulate = [&$rows];
        } else {
            $rowsToPopulate = &$rows;
        }
        $populate($rowsToPopulate);
    }

    /**
     * Expand an array of tagIDs.
     *
     * @param array $rows
     */
    public function expandTagIDs(array &$rows)
    {
        ModelUtils::leftJoin($rows, ["discussionID" => "tagIDs"], [$this, "getTagIDsForDiscussionIDs"]);
    }

    /**
     * Get TagIDs for a group of discussions.
     *
     * @param array $discussionIDs
     * @return array
     */
    public function getTagIDsForDiscussionIDs(array $discussionIDs): array
    {
        $tagDiscussions = $this->SQL
            ->select("td.TagID, td.DiscussionID")
            ->from("TagDiscussion td")
            ->join("Discussion d", "d.DiscussionID = td.DiscussionID")
            ->whereIn("td.DiscussionID", $discussionIDs)
            ->get()
            ->resultArray();

        $tagIDsByDiscussionID = ArrayUtils::arrayColumnArrays($tagDiscussions, "TagID", "DiscussionID");
        return $tagIDsByDiscussionID;
    }

    /**
     * Get DiscussionIDs from a group of tags.
     *
     * @param array $where
     * @return array
     */
    public function getTagDiscussionIDs(array $where): array
    {
        $discussionIDs = $this->SQL
            ->select("DiscussionID")
            ->distinct()
            ->from("TagDiscussion")
            ->where($where)
            ->get()
            ->resultArray();

        return $discussionIDs;
    }

    /**
     *
     *
     * @param $tag
     * @return bool
     */
    public static function validateTag($tag)
    {
        // Tags can't contain commas or underscores.
        if (preg_match("/[,_]/", $tag)) {
            return false;
        }
        return true;
    }

    /**
     *
     *
     * @param $tags
     * @return bool
     */
    public static function validateTags($tags)
    {
        if (is_string($tags)) {
            $tags = self::splitTags($tags);
        }

        foreach ($tags as $tag) {
            if (!self::validateTag($tag)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     *
     * @param $type
     * @return bool
     */
    public function validateType($type)
    {
        $validType = false;
        $tagTypes = $this->types();

        foreach ($tagTypes as $typeKey => $typeMeta) {
            $typeChecks = [
                strtolower($typeKey),
                strtolower($typeMeta["key"]),
                strtolower($typeMeta["name"]),
                strtolower($typeMeta["plural"]),
            ];

            if (in_array(strtolower($type), $typeChecks)) {
                $validType = true;
                break;
            }
        }

        return $validType;
    }

    /**
     * Checks to see if the tag type allows new tags to be added to it.
     *
     * @param string $type
     * @return bool
     */
    public function canAddTagForType($type)
    {
        $canAddTagForType = false;
        $tagTypes = $this->types();

        foreach ($tagTypes as $typeKey => $typeMeta) {
            $typeChecks = [strtolower($typeKey), strtolower($typeMeta["key"]), strtolower($typeMeta["name"])];

            if (in_array(strtolower($type), $typeChecks) && $typeMeta["addtag"]) {
                $canAddTagForType = true;
                break;
            }
        }

        return $canAddTagForType;
    }

    /**
     *
     *
     * @param $tagsString
     * @return array
     */
    public static function splitTags($tagsString)
    {
        $tags = preg_split("`[,]`", $tagsString);
        // Trim each tag.
        foreach ($tags as $index => $tag) {
            $tag = trim($tag);
            if (!$tag) {
                unset($tags[$index]);
            } else {
                $tags[$index] = $tag;
            }
        }
        $tags = array_unique($tags);
        return $tags;
    }

    /**
     *
     *
     * @param $str
     * @return string
     */
    public static function tagSlug($str)
    {
        return rawurldecode(Gdn_Format::url($str));
    }

    /**
     * Search results for tagging autocomplete.
     *
     * @param string $q
     * @param bool $id
     * @param bool|int|array $parent
     * @param string|array $type
     * @param array $options
     * @return array
     */
    public function search($q = "", $id = false, $parent = false, $type = "tag", array $options = []): array
    {
        // Allow per-category tags
        $categorySearch = c("Vanilla.Tagging.CategorySearch", false);
        if ($categorySearch) {
            $categoryID = $options["categoryID"] ?? null;
        }

        // Turn the parent(s) into an array of ids.
        if ($parent) {
            $parent = (array) $parent;
        }

        // Make sure type is an array.
        $type = (array) $type;

        $query = $q;
        $data = [];
        $database = Gdn::database();
        if ($query || !empty($parent) || !empty($type)) {
            $tagQuery = Gdn::sql()
                ->select("*")
                ->from("Tag");

            if (key_exists("sort", $options)) {
                $tagQuery->orderBy($options["sort"], "desc");
            }

            if (key_exists("limit", $options)) {
                $offset = $options["offset"] ?? 0;
                $tagQuery->limit($options["limit"], $offset);
            }

            if ($query) {
                $tagQuery->like(
                    "FullName",
                    str_replace(["%", "_"], ["\%", "_"], $query),
                    strlen($query) > 2 ? "both" : "right"
                );
            }

            if (in_array("tag", $type)) {
                $searchableTypes = $this->getAllowedTagTypes();
                $tagQuery->where("Type", $searchableTypes); // Other UIs can set a different type
            } elseif (!in_array("all", $type)) {
                $tagQuery->whereIn("Type", $type);
            }

            // Allow per-category tags
            if ($categorySearch) {
                $tagQuery->where("CategoryID", $categoryID);
            }

            if ($parent) {
                $tagQuery->whereIn("ParentTagID", $parent);
            }

            if (!empty($options["excludeNoCountDiscussion"])) {
                $tagQuery->where("CountDiscussions >", 0);
            }

            // Run tag search query
            $tagData = $tagQuery->get();

            $extraFields = $options["extraFields"] ?? false;

            foreach ($tagData as $tag) {
                if ($extraFields) {
                    $type = $tag->Type ?? "";
                    $id = $tag->TagID ?? null;
                    $data[] = [
                        "id" => $id,
                        "name" => $tag->Name,
                        "fullName" => $tag->FullName,
                        "type" => $type,
                        "parentTagID" => $tag->ParentTagID ?? null,
                        "countDiscussions" => $tag->CountDiscussions,
                    ];
                } else {
                    $data[] = [
                        "id" => $id ? $tag->TagID : $tag->Name,
                        "name" => $tag->FullName,
                    ];
                }
            }
        }
        $database->closeConnection();
        return $data;
    }

    /**
     * Checks to see if the number of tags being added exceeds the maximum number of tags allowed on the discussion.
     *
     * @param array $tags
     * @throws ClientException Throws an error if there are more tags than are allowed.
     */
    private function checkMaxTagsLimit($tags): void
    {
        $maxTags = Gdn::config("Vanilla.Tagging.Max", 5);
        if (count($tags) > $maxTags) {
            throw new ClientException(
                sprintf(
                    'You cannot add more than %1$s %2$s to a discussion',
                    $maxTags,
                    plural($maxTags, "tag", "tags")
                ),
                409
            );
        }
    }

    /**
     * Check to see what tag types you can allow to a discussion.
     *
     * @param array $tags The array of tags to check.
     * @throws ClientException Throws an exception if a tag type isn't allowed.
     */
    public function checkAllowedDiscussionTagTypes(array $tags): void
    {
        $allowedTypes = Gdn::config("Tagging.Discussions.AllowedTypes", [""]);
        foreach ($tags as $tag) {
            if (!in_array($tag["Type"], $allowedTypes)) {
                throw new ClientException(
                    sprintf("You cannot add tags with a type of %s to a discussion", $tag["Type"]),
                    409
                );
            }
        }
    }

    /**
     * Given an array of discussion IDs, get all their tags, indexed by discussion ID.
     *
     * @param int[] $discussionIDs
     * @param int[] $tagIDs
     * @return array
     */
    public function getTagsByDiscussionIDs(array $discussionIDs, array $tagIDs = []): array
    {
        if (empty($discussionIDs)) {
            return [];
        }

        $validateIDs = function (array $input, string $exceptionMessage): array {
            $values = array_values($input);
            array_walk($values, function ($discussionID) use ($exceptionMessage) {
                if (filter_var($discussionID, FILTER_VALIDATE_INT) === false) {
                    throw new InvalidArgumentException($exceptionMessage, 400);
                }
            });
            return $values;
        };

        $discussionIDs = $validateIDs($discussionIDs, "Invalid discussion ID array specified.");
        $tagIDs = $validateIDs($tagIDs, "Invalid tag ID array specified.");

        $query = Gdn::sql()
            ->select("td.DiscussionID")
            ->select("t.*")
            ->from("TagDiscussion td")
            ->join("Tag t", "td.TagID = t.TagID")
            ->whereIn("td.DiscussionID", $discussionIDs);

        if (!empty($tagIDs)) {
            $query->whereIn("td.TagID", $tagIDs);
        }

        $tags = $query->get()->resultArray();

        $result = [];
        foreach ($tags as $tag) {
            $discussionID = $tag["DiscussionID"];
            unset($tag["DiscussionID"]);
            $tagID = $tag["TagID"];
            $result[$discussionID][$tagID] = $tag;
        }
        return $result;
    }

    /**
     * Get all the tag types for which a user can add the tags to a discussion.
     *
     * @return array
     */
    public function getAllowedTagTypes(): array
    {
        $defaultTypes = array_keys(TagModel::instance()->defaultTypes());
        $allowedTypes = (array) Gdn::config("Tagging.Discussions.AllowedTypes", []);
        $searchableTypes = array_unique(array_merge($allowedTypes, $defaultTypes));
        return $searchableTypes;
    }
}
