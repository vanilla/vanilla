<?php
/**
 * Tagging plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Tagging
 */

class TagModel extends Gdn_Model {

    const IX_EXTENDED = 'x';

    const IX_TAGID = 'id';

    protected $Types;

    protected static $instance;

    public $StringTags;

    /**
     * @param string $name
     */
    public function __construct($name = '') {
        parent::__construct('Tag');
        $this->StringTags = c('Vanilla.Tagging.StringTags');
    }

    /**
     * The singleton instance of this object.
     * @return TagModel
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new TagModel();
        }
        return self::$instance;
    }

    /**
     *
     *
     * @return array
     */
    public function defaultTypes() {
        $types = array_filter($this->types(), function($val) {
            if (val('default', $val)) {
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
     * @return bool|unknown
     * @throws Exception
     */
    public function save($formPostValues, $settings = false) {
        // Get the ID of an existing tag with the same name.
        $existingTag = $this->getWhere(['Name' => $formPostValues['Name'], 'TagID <>' => val('TagID', $formPostValues)])->firstRow(DATASET_TYPE_ARRAY);
        if ($existingTag) {
            if (!val('TagID', $formPostValues)) {
                return $existingTag['TagID'];
            }

            // This tag will be merged with the existing one.
            $px = $this->Database->DatabasePrefix;
            $fromID = $formPostValues['TagID'];
            $toID = $existingTag['TagID'];

            try {
                $this->Database->beginTransaction();

                // Delete all of the overlapping tags.
                $sql = "delete tg.*
               from {$px}TagDiscussion tg
               join {$px}TagDiscussion tg2
                 on tg.DiscussionID = tg2.DiscussionID
                   and tg.TagID = :FromID and tg2.TagID = :ToID";
                $this->Database->query($sql, [':FromID' => $fromID, ':ToID' => $toID]);

                // Update the tagged discussions.
                $sql = "update {$px}TagDiscussion
               set TagID = :ToID
               where TagID = :FromID";
                $this->Database->query($sql, [':FromID' => $fromID, ':ToID' => $toID]);

                // Update the counts
                $this->updateTagCountDiscussions($toID);

                // Delete the old tag.
                $sql = "delete from {$px}Tag where TagID = :FromID";
                $this->Database->query($sql, [':FromID' => $fromID]);

                $this->Database->commitTransaction();
            } catch (Exception $ex) {
                $this->Database->rollbackTransaction();
                throw $ex;
            }

            return $toID;
        } else {
            if (Gdn::session()->checkPermission('Vanilla.Tagging.Add')) {
                // Tag-type tags (i.e., user generated tags) are saved with no type.
                if (strtolower(val('Type', $formPostValues)) == 'tag') {
                    $formPostValues['Type'] = '';
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
    public function addType($key, $row) {
        $row['key'] = $key;
        $this->Types[$key] = $row;
    }

    /**
     * Get the available tag types.
     */
    public function types() {
        if (!isset($this->Types)) {
            $this->Types = [
                '' => [
                    'key' => '',
                    'name' => 'All',
                    'plural' => 'All',
                    'default' => true,
                    'addtag' => false
                ],
                'tags' => [
                    'key' => 'tags',
                    'name' => 'Tag',
                    'plural' => 'Tags',
                    'default' => false,
                    'addtag' => true
                ]
            ];

            $this->fireEvent('Types');
        }

        return $this->Types;
    }

    /**
     *
     *
     * @return array
     */
    public function getTagTypes() {
        $tagTypes = $this->types();

        if (!is_array($tagTypes)) {
            $tagTypes = [];
        }

        // Remove the defaults out of the list, and add them to the start
        $start = array_intersect_key($tagTypes, ['' => [], 'tags' => []]);
        unset($tagTypes['']);
        unset($tagTypes['tags']);

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
    public function updateTagCountDiscussions($tagID) {
        $px = $this->Database->DatabasePrefix;
        // Update the counts.
        $sql = "update {$px}Tag t
         set CountDiscussions = (
            select count(DiscussionID)
            from {$px}TagDiscussion td
            where td.TagID = t.TagID)
          where t.TagID = :TagID";
        $this->Database->query($sql, [':TagID' => $tagID]);
    }

    /**
     * Get all of the tags related to the current tag.
     *
     * @param mixed $tag
     */
    public function getRelatedTags($tag) {
        if (is_numeric($tag)) {
            $tag = $this->getID($tag, DATASET_TYPE_ARRAY);
        }
        if (!is_array($tag)) {
            return [];
        }

        $result = [
            $tag['Type'] => [$tag]
        ];

        // Get all of the parent tags.
        for ($i = 0, $parentid = val('ParentTagID', $tag);
             $parentid && $i < 10;
             $i++, $parentid = val('ParentTagID', $tag)) {
            $tag = $this->getID($parentid, DATASET_TYPE_ARRAY);
            if (!$tag) {
                break;
            }

            $result[$tag['Type']][] = $tag;
        }
        return $result;
    }

    /**
     * Get the child tags associated with the parent tag id.
     *
     * @param int $parentTagID The parent tag ID to check for children.
     * @return array All child tag rows
     */
    public function getChildTags($parentTagID) {
        $childTags = $this->getWhere(['ParentTagID' => $parentTagID])->resultArray();
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
    public function getDiscussionTags($discussionID, $indexed = true) {
        $tags = Gdn::sql()->select('t.*')
            ->from('TagDiscussion td')
            ->join('Tag t', 'td.TagID = t.TagID')
            ->where('td.DiscussionID', $discussionID)
            ->get()->resultArray();

        if ($indexed) {
            if ($indexed === TagModel::IX_TAGID) {
                $tags = Gdn_DataSet::index($tags, 'TagID');
            } else {
                // The tags are indexed by type.
                $tags = Gdn_DataSet::index($tags, 'Type', ['Unique' => false]);
                if ($indexed === TagModel::IX_EXTENDED) {
                    // The tags are indexed by type, but tags with no type are seperated.
                    if (array_key_exists('', $tags)) {
                        $tags = ['Tags' => $tags[''], 'XTags' => $tags];
                        unset($tags['XTags']['']);
                    } else {
                        $tags = ['Tags' => [], 'XTags' => $tags];
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
    public function joinTags(&$data) {
        // If we're dealing with an instance of Gdn_Dataset, grab a reference to its results.
        if ($data instanceof Gdn_DataSet) {
            $rows = $data->result();
        } else {
            $rows = &$data;
        }

        $ids = [];
        foreach ($rows as $row) {
            $discussionId = val('DiscussionID', $row);
            if ($discussionId) {
                $ids[] = $discussionId;
            }
        }

        // Select the tags.
        $all_tags = $this->SQL->select('td.DiscussionID, t.TagID, t.Name, t.FullName')
            ->from('TagDiscussion td')
            ->join('Tag t', 't.TagID = td.TagID')
            ->whereIn('td.DiscussionID', $ids)
            ->get()->resultArray();

        $all_tags = Gdn_DataSet::index($all_tags, 'DiscussionID', ['Unique' => false]);

        foreach ($rows as &$row) {
            $discussionId = val('DiscussionID', $row);
            if (isset($all_tags[$discussionId])) {
                $tags = $all_tags[$discussionId];

                if ($this->StringTags) {
                    $tags = array_column($tags, 'Name');
                    setValue('Tags', $row, implode(',', $tags));
                } else {
                    foreach ($tags as &$trow) {
                        unset($trow['DiscussionID']);
                    }
                    setValue('Tags', $row, $tags);
                }
            } else {
                if ($this->StringTags) {
                    setValue('Tags', $row, '');
                } else {
                    setValue('Tags', $row, []);
                }
            }
        }

    }

    /**
     * Add existing tags to a discussion.
     *
     * @param int $discussionID The ID of the discussion to add the tags to.
     * @param array $tags An array of tag IDs.
     * @throws Gdn_UserException Throws an exception if some of the tags don't exist.
     */
    public function addDiscussion($discussionID, $tags) {
        $tagIDs = array_unique($tags);

        $validTags = $this->SQL->select('*')
            ->from('Tag')
            ->where('TagID', $tagIDs)
            ->get()->resultArray();

        if (count($tagIDs) != count($validTags)) {
            throw new Gdn_UserException('Non existing tag(s) supplied.');
        }

        $tagsToAdd = $tagIDs;

        $currentTags = $this->getDiscussionTags($discussionID, TagModel::IX_TAGID);
        if ($currentTags) {
            $tagsToAdd = array_diff($tagsToAdd, array_keys($currentTags));
        }

        if (!empty($tagsToAdd)) {
            $now = Gdn_Format::toDateTime();

            // Insert new tags
            foreach($tagsToAdd as $tagID) {
                $this->SQL
                    ->options('Ignore', true)
                    ->insert(
                        'TagDiscussion',
                        ['DiscussionID' => $discussionID, 'TagID' => $tagID, 'DateInserted' => $now, 'CategoryID' => $validTags[$tagID]['CategoryID']]
                    );
            }

            // Increment the tag counts.
            $this->SQL->update('Tag')
                ->set('CountDiscussions', 'CountDiscussions + 1', false)
                ->whereIn('TagID', $tagsToAdd)
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
    public function saveDiscussion($discussion_id, $tags, $types = [''], $category_id = 0, $new_type = '') {
        // First grab all of the current tags.
        $all_tags = $current_tags = $this->getDiscussionTags($discussion_id, TagModel::IX_TAGID);

        // Put all the default tag types in the types if necessary.
        if (in_array('', $types)) {
            $types = array_merge($types, array_keys($this->defaultTypes()));
            $types = array_unique($types);
        }

        // Remove the types from the current tags that we don't need anymore.
        $current_tags = array_filter($current_tags, function($row) use ($types) {
            if (in_array($row['Type'], $types)) {
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
            $found_tags = $this->getWhere(['Name' => array_keys($new_tags)])->resultArray();
            foreach ($found_tags as $found_tag_row) {
                $tag_ids[$found_tag_row['TagID']] = $found_tag_row;
                unset($new_tags[TagModel::tagSlug($found_tag_row['Name'])]);
            }
        }

        // Add any remaining tags that need to be added.
        if (Gdn::session()->checkPermission('Vanilla.Tagging.Add')) {
            foreach ($new_tags as $name => $full_name) {
                $new_tag = [
                    'Name' => trim(str_replace(' ', '-', strtolower($name)), '-'),
                    'FullName' => $full_name,
                    'Type' => $new_type,
                    'CategoryID' => $category_id,
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'CountDiscussions' => 0
                ];
                $tag_id = $this->SQL->options('Ignore', true)->insert('Tag', $new_tag);
                $tag_ids[$tag_id] = true;
            }
        }

        // Grab the tags so we can see more information about them.
        $save_tags = $this->getWhere(['TagID' => array_keys($tag_ids)])->resultArray();
        // Add any parent tags that may need to be added.
        foreach ($save_tags as $save_tag) {
            $parent_tag_id = val('ParentTagID', $save_tag);
            if ($parent_tag_id) {
                $tag_ids[$parent_tag_id] = true;
            }
            $all_tags[$save_tag['TagID']] = $save_tag;
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

        // Insert the new tag mappings.
        foreach ($insert_tag_ids as $tag_id => $bool) {
            if (isset($all_tags[$tag_id])) {
                $insert_category_id = $all_tags[$tag_id]['CategoryID'];
            } else {
                $insert_category_id = $category_id;
            }

            $this->SQL->options('Ignore', true)->insert(
                'TagDiscussion',
                ['DiscussionID' => $discussion_id, 'TagID' => $tag_id, 'DateInserted' => $now, 'CategoryID' => $insert_category_id]
            );
        }

        // Delete the old tag mappings.
        if (!empty($delete_tag_ids)) {
            $this->SQL->delete('TagDiscussion', ['DiscussionID' => $discussion_id, 'TagID' => array_keys($delete_tag_ids)]);
        }

        // Increment the tag counts.
        if (!empty($insert_tag_ids)) {
            $this->SQL->update('Tag')->set('CountDiscussions', 'CountDiscussions + 1', false)->whereIn('TagID', array_keys($insert_tag_ids))->put();
        }

        // Decrement the tag counts.
        if (!empty($delete_tag_ids)) {
            $this->SQL->update('Tag')->set('CountDiscussions', 'CountDiscussions - 1', false)->whereIn('TagID', array_keys($delete_tag_ids))->put();
        }
    }

    /**
     * Get discussions by tag(s)
     *
     * @param string|array $tag tag name(s)
     * @param int $limit limit number of result
     * @param int $offset start result at this offset
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getDiscussions($tag, $limit, $offset) {
        if (!is_array($tag)) {
            $tags = array_map('trim', explode(',', $tag));
        }

        $taggedDiscussionIDs = $this->SQL
            ->select('td.DiscussionID')
            ->from('TagDiscussion td')
            ->join('Tag t', 't.TagID = td.TagID')
            ->whereIn('t.Name', $tags)
            ->limit($limit, $offset)
            ->get()->resultArray();

        $taggedDiscussionIDs = array_column($taggedDiscussionIDs, 'DiscussionID');

        $discussionModel = new DiscussionModel();
        $discussions = $discussionModel->get(
            0,
            '',
            [
                'Announce' => 'all',
                'd.DiscussionID' => $taggedDiscussionIDs,
            ]
        );

        return $discussions;
    }

    /**
     * @deprecated
     *
     * @param Gdn_SQLDriver $sql
     */
    public function setTagSql($sql, $tag, &$limit, &$offset = 0, $op = 'or') {
        deprecated('TagModel->setTagSql()', 'TagModel->getDiscussions()', '2018-06-19');
        $sortField = 'd.DateLastComment';
        $sortDirection = 'desc';
        $tagSql = clone Gdn::sql();
        if ($dateFrom = Gdn::request()->get('DateFrom')) {
            // Find the discussion ID of the first discussion created on or after the date from.
            $discussionIDFrom = $tagSql->getWhere('Discussion', ['DateInserted >= ' => $dateFrom], 'DiscussionID', 'asc', 1)->value('DiscussionID');
            $sortField = 'd.DiscussionID';
        }
        if (!is_array($tag)) {
            $tags = array_map('trim', explode(',', $tag));
        }
        $tagIDs = $tagSql
            ->select('TagID')
            ->from('Tag')
            ->whereIn('Name', $tags)
            ->get()->resultArray();
        $tagIDs = array_column($tagIDs, 'TagID');
        if ($op == 'and' && count($tags) > 1) {
            $discussionIDs = $tagSql
                ->select('DiscussionID')
                ->select('TagID', 'count', 'CountTags')
                ->from('TagDiscussion')
                ->whereIn('TagID', $tagIDs)
                ->groupBy('DiscussionID')
                ->having('CountTags >=', count($tags))
                ->limit($limit, $offset)
                ->orderBy('DiscussionID', 'desc')
                ->get()->resultArray();
            $limit = '';
            $offset = 0;
            $discussionIDs = array_column($discussionIDs, 'DiscussionID');
            $sql->whereIn('d.DiscussionID', $discussionIDs);
            $sortField = 'd.DiscussionID';
        } else {
            $sql
                ->join('TagDiscussion td', 'd.DiscussionID = td.DiscussionID')
                ->limit($limit, $offset)
                ->whereIn('td.TagID', $tagIDs);
            if ($op == 'and') {
                $sortField = 'd.DiscussionID';
            }
        }
        // Set up the sort field and direction.
        saveToConfig(
            [
                'Vanilla.Discussions.SortField' => $sortField,
                'Vanilla.Discussions.SortDirection' => $sortDirection],
            '',
            false
        );
    }

    /**
     * Unpivot tags that are grouped by type.
     *
     * @param array $tags
     * @return array
     */
    public function unpivot($tags) {
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
    public function counts($column, $userID = null) {
        // Delete all the orphaned tagdiscussion records
        $px = $this->Database->DatabasePrefix;
        $sql = "delete td.* from {$px}TagDiscussion as td left join {$px}Discussion as d ON td.DiscussionID = d.DiscussionID where d.DiscussionID is null";
        $this->Database->query($sql);

        $result = ['Complete' => true];
        switch ($column) {
            case 'CountDiscussions':
                Gdn::database()->query(DBAModel::getCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'DiscussionID', 'TagID', 'TagID'));
                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }
        return $result;
    }

    /**
     *
     *
     * @param $tag
     * @return bool
     */
    public static function validateTag($tag) {
        // Tags can't contain commas.
        if (preg_match('`,`', $tag)) {
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
    public static function validateTags($tags) {
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
    public function validateType($type) {
        $validType = false;
        $tagTypes = $this->types();

        foreach ($tagTypes as $typeKey => $typeMeta) {
            $typeChecks = [
                strtolower($typeKey),
                strtolower($typeMeta['key']),
                strtolower($typeMeta['name']),
                strtolower($typeMeta['plural'])
            ];

            if (in_array(strtolower($type), $typeChecks)) {
                $validType = true;
                break;
            }
        }

        return $validType;
    }

    /**
     *
     *
     * @param $type
     * @return bool
     */
    public function canAddTagForType($type) {
        $canAddTagForType = false;
        $tagTypes = $this->types();

        foreach ($tagTypes as $typeKey => $typeMeta) {
            $typeChecks = [
                strtolower($typeKey),
                strtolower($typeMeta['key']),
                strtolower($typeMeta['name']),
                strtolower($typeMeta['plural'])
            ];

            if (in_array(strtolower($type), $typeChecks)
                && $typeMeta['addtag']
            ) {
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
    public static function splitTags($tagsString) {
        $tags = preg_split('`[,]`', $tagsString);
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
    public static function tagSlug($str) {
        return rawurldecode(Gdn_Format::url($str));
    }
}
