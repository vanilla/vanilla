<?php
/**
 * Tagging plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param string $Name
     */
    public function __construct($Name = '') {
        parent::__construct('Tag');
        $this->StringTags = c('Plugins.Tagging.StringTags');
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
        $types = array_filter($this->types(), function ($val) {
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
     * @param array $FormPostValues
     * @param bool $Settings
     * @return bool|unknown
     * @throws Exception
     */
    public function save($FormPostValues, $Settings = false) {
        // Get the ID of an existing tag with the same name.
        $ExistingTag = $this->getWhere(array('Name' => $FormPostValues['Name'], 'TagID <>' => val('TagID', $FormPostValues)))->firstRow(DATASET_TYPE_ARRAY);
        if ($ExistingTag) {
            if (!val('TagID', $FormPostValues)) {
                return $ExistingTag['TagID'];
            }

            // This tag will be merged with the existing one.
            $Px = $this->Database->DatabasePrefix;
            $FromID = $FormPostValues['TagID'];
            $ToID = $ExistingTag['TagID'];

            try {
                $this->Database->beginTransaction();

                // Delete all of the overlapping tags.
                $Sql = "delete tg.*
               from {$Px}TagDiscussion tg
               join {$Px}TagDiscussion tg2
                 on tg.DiscussionID = tg2.DiscussionID
                   and tg.TagID = :FromID and tg2.TagID = :ToID";
                $this->Database->query($Sql, array(':FromID' => $FromID, ':ToID' => $ToID));

                // Update the tagged discussions.
                $Sql = "update {$Px}TagDiscussion
               set TagID = :ToID
               where TagID = :FromID";
                $this->Database->query($Sql, array(':FromID' => $FromID, ':ToID' => $ToID));

                // Update the counts
                $this->updateTagCountDiscussions($ToID);

                // Delete the old tag.
                $Sql = "delete from {$Px}Tag where TagID = :FromID";
                $this->Database->query($Sql, array(':FromID' => $FromID));

                $this->Database->commitTransaction();
            } catch (Exception $Ex) {
                $this->Database->rollbackTransaction();
                throw $Ex;
            }

            return $ToID;
        } else {
            if (Gdn::session()->checkPermission('Plugins.Tagging.Add')) {
                return parent::save($FormPostValues, $Settings);
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
            $this->Types = array(
                '' => array(
                    'key' => '',
                    'name' => 'Tag',
                    'plural' => 'Tags',
                    'default' => true,
                    'addtag' => true
                )
            );

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
        $TagTypes = $this->types();

        if (!is_array($TagTypes)) {
            $TagTypes = array();
        }

        // Sort by keys, and because the default, "Tags," has a blank key, it
        // will be set as the first key, which is good for the tabs.
        if (count($TagTypes)) {
            ksort($TagTypes);
        }

        return $TagTypes;
    }

    /**
     * Update the tag count per discussion in the Tag table
     *
     * @param int $TagID
     */
    public function updateTagCountDiscussions($TagID) {
        $Px = $this->Database->DatabasePrefix;
        // Update the counts.
        $Sql = "update {$Px}Tag t
         set CountDiscussions = (
            select count(DiscussionID)
            from {$Px}TagDiscussion td
            where td.TagID = t.TagID)
          where t.TagID = :TagID";
        $this->Database->query($Sql, array(':TagID' => $TagID));
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
            return array();
        }

        $result = array(
            $tag['Type'] => array($tag)
        );

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
        $childTags = $this->getWhere(array('ParentTagID' => $parentTagID))->resultArray();
        if (!count(array_filter($childTags))) {
            $childTags = array();
        }

        return $childTags;
    }

    /**
     * Get detailed tag data for a given discussion. An example use case would
     * be when editing discussions: any non-typical tags, that is, ones that
     * may appear to be categories, should have their specific data available,
     * like Type, or Source.
     *
     * @param int $DiscussionID
     * @return array
     */
    public function getDiscussionTags($DiscussionID, $indexed = true) {
        $Tags = Gdn::sql()->select('t.*')
            ->from('TagDiscussion td')
            ->join('Tag t', 'td.TagID = t.TagID')
            ->where('td.DiscussionID', $DiscussionID)
            ->get()->resultArray();

        if ($indexed) {
            if ($indexed === TagModel::IX_TAGID) {
                $Tags = Gdn_DataSet::index($Tags, 'TagID');
            } else {
                // The tags are indexed by type.
                $Tags = Gdn_DataSet::index($Tags, 'Type', array('Unique' => false));
                if ($indexed === TagModel::IX_EXTENDED) {
                    // The tags are indexed by type, but tags with no type are seperated.
                    if (array_key_exists('', $Tags)) {
                        $Tags = array('Tags' => $Tags[''], 'XTags' => $Tags);
                        unset($Tags['XTags']['']);
                    } else {
                        $Tags = array('Tags' => array(), 'XTags' => $Tags);
                    }
                }
            }
        }

        return $Tags;
    }

    /**
     * Join the tags to a set of discussions.
     *
     * @param $data
     */
    public function joinTags(&$data) {
        $ids = array();
        foreach ($data as $row) {
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

        $all_tags = Gdn_DataSet::index($all_tags, 'DiscussionID', array('Unique' => false));

        foreach ($data as &$row) {
            $discussionId = val('DiscussionID', $row);
            if (isset($all_tags[$discussionId])) {
                $tags = $all_tags[$discussionId];

                if ($this->StringTags) {
                    $tags = consolidateArrayValuesByKey($tags, 'Name');
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
                    setValue('Tags', $row, array());
                }
            }
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
    public function saveDiscussion($discussion_id, $tags, $types = array(''), $category_id = 0, $new_type = '') {
        // First grab all of the current tags.
        $all_tags = $current_tags = $this->getDiscussionTags($discussion_id, TagModel::IX_TAGID);

        // Put all the default tag types in the types if necessary.
        if (in_array('', $types)) {
            $types = array_merge($types, array_keys($this->defaultTypes()));
            $types = array_unique($types);
        }

        // Remove the types from the current tags that we don't need anymore.
        $current_tags = array_filter($current_tags, function ($row) use ($types) {
            if (in_array($row['Type'], $types)) {
                return true;
            }
            return false;
        });

        // Turn the tags into a nice array.
        if (is_string($tags)) {
            $tags = TagModel::SplitTags($tags);
        }

        $new_tags = array();
        $tag_ids = array();

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
            $found_tags = $this->getWhere(array('Name' => array_keys($new_tags)))->resultArray();
            foreach ($found_tags as $found_tag_row) {
                $tag_ids[$found_tag_row['TagID']] = $found_tag_row;
                unset($new_tags[TagModel::TagSlug($found_tag_row['Name'])]);
            }
        }

        // Add any remaining tags that need to be added.
        if (Gdn::session()->checkPermission('Plugins.Tagging.Add')) {
            foreach ($new_tags as $name => $full_name) {
                $new_tag = array(
                    'Name' => trim(str_replace(' ', '-', strtolower($name)), '-'),
                    'FullName' => $full_name,
                    'Type' => $new_type,
                    'CategoryID' => $category_id,
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'CountDiscussions' => 0
                );
                $tag_id = $this->SQL->options('Ignore', true)->insert('Tag', $new_tag);
                $tag_ids[$tag_id] = true;
            }
        }

        // Grab the tags so we can see more information about them.
        $save_tags = $this->getWhere(array('TagID' => array_keys($tag_ids)))->resultArray();
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
                array('DiscussionID' => $discussion_id, 'TagID' => $tag_id, 'DateInserted' => $now, 'CategoryID' => $insert_category_id)
            );
        }

        // Delete the old tag mappings.
        if (!empty($delete_tag_ids)) {
            $this->SQL->delete('TagDiscussion', array('DiscussionID' => $discussion_id, 'TagID' => array_keys($delete_tag_ids)));
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
     *
     *
     * @param $Tag
     * @param $Limit
     * @param $Offset
     * @param string $Op
     * @return Gdn_DataSet
     */
    public function getDiscussions($Tag, $Limit, $Offset, $Op = 'or') {
        $DiscussionModel = new DiscussionModel();
        $this->setTagSql($DiscussionModel->SQL, $Tag, $Limit, $Offset, $Op);
        $Result = $DiscussionModel->get($Offset, $Limit, array('Announce' => 'all'));

        return $Result;
    }

    /**
     *
     *
     * @param Gdn_SQLDriver $Sql
     */
    public function setTagSql($Sql, $Tag, &$Limit, &$Offset = 0, $Op = 'or') {
        $SortField = 'd.DateLastComment';
        $SortDirection = 'desc';

        $TagSql = clone Gdn::sql();

        if ($DateFrom = Gdn::request()->get('DateFrom')) {
            // Find the discussion ID of the first discussion created on or after the date from.
            $DiscussionIDFrom = $TagSql->getWhere('Discussion', array('DateInserted >= ' => $DateFrom), 'DiscussionID', 'asc', 1)->value('DiscussionID');
            $SortField = 'd.DiscussionID';
        }

        if (!is_array($Tag)) {
            $Tags = array_map('trim', explode(',', $Tag));
        }
        $TagIDs = $TagSql
            ->select('TagID')
            ->from('Tag')
            ->whereIn('Name', $Tags)
            ->get()->resultArray();

        $TagIDs = consolidateArrayValuesByKey($TagIDs, 'TagID');

        if ($Op == 'and' && count($Tags) > 1) {
            $DiscussionIDs = $TagSql
                ->select('DiscussionID')
                ->select('TagID', 'count', 'CountTags')
                ->from('TagDiscussion')
                ->whereIn('TagID', $TagIDs)
                ->groupBy('DiscussionID')
                ->having('CountTags >=', count($Tags))
                ->limit($Limit, $Offset)
                ->orderBy('DiscussionID', 'desc')
                ->get()->resultArray();
            $Limit = '';
            $Offset = 0;

            $DiscussionIDs = consolidateArrayValuesByKey($DiscussionIDs, 'DiscussionID');

            $Sql->whereIn('d.DiscussionID', $DiscussionIDs);
            $SortField = 'd.DiscussionID';
        } else {
            $Sql
                ->join('TagDiscussion td', 'd.DiscussionID = td.DiscussionID')
                ->limit($Limit, $Offset)
                ->whereIn('td.TagID', $TagIDs);

            if ($Op == 'and') {
                $SortField = 'd.DiscussionID';
            }
        }

        // Set up the sort field and direction.
        saveToConfig(
            array(
            'Vanilla.Discussions.SortField' => $SortField,
            'Vanilla.Discussions.SortDirection' => $SortDirection),
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
        $result = array();
        foreach ($tags as $rows) {
            $result = array_merge($result, $rows);
        }
        return $result;
    }

    /**
     *
     *
     * @param $Column
     * @param null $UserID
     * @return array
     * @throws Gdn_UserException
     */
    public function counts($Column, $UserID = null) {
        // Delete all the orphaned tagdiscussion records
        $Px = $this->Database->DatabasePrefix;
        $Sql = "delete td.* from {$Px}TagDiscussion as td left join {$Px}Discussion as d ON td.DiscussionID = d.DiscussionID where d.DiscussionID is null";
        $this->Database->query($Sql);

        $Result = array('Complete' => true);
        switch ($Column) {
            case 'CountDiscussions':
                Gdn::database()->query(DBAModel::getCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'DiscussionID', 'TagID', 'TagID'));
                break;
            default:
                throw new Gdn_UserException("Unknown column $Column");
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Tag
     * @return bool
     */
    public static function validateTag($Tag) {
        // Tags can't contain commas.
        if (preg_match('`,`', $Tag)) {
            return false;
        }
        return true;
    }

    /**
     *
     *
     * @param $Tags
     * @return bool
     */
    public static function validateTags($Tags) {
        if (is_string($Tags)) {
            $Tags = self::splitTags($Tags);
        }

        foreach ($Tags as $Tag) {
            if (!self::validateTag($Tag)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     *
     * @param $Type
     * @return bool
     */
    public function validateType($Type) {
        $ValidType = false;
        $TagTypes = $this->types();

        foreach ($TagTypes as $TypeKey => $TypeMeta) {
            $TypeChecks = array(
                strtolower($TypeKey),
                strtolower($TypeMeta['key']),
                strtolower($TypeMeta['name']),
                strtolower($TypeMeta['plural'])
            );

            if (in_array(strtolower($Type), $TypeChecks)) {
                $ValidType = true;
                break;
            }
        }

        return $ValidType;
    }

    /**
     *
     *
     * @param $Type
     * @return bool
     */
    public function canAddTagForType($Type) {
        $CanAddTagForType = false;
        $TagTypes = $this->types();

        foreach ($TagTypes as $TypeKey => $TypeMeta) {
            $TypeChecks = array(
                strtolower($TypeKey),
                strtolower($TypeMeta['key']),
                strtolower($TypeMeta['name']),
                strtolower($TypeMeta['plural'])
            );

            if (in_array(strtolower($Type), $TypeChecks)
                && $TypeMeta['addtag']
            ) {
                $CanAddTagForType = true;
                break;
            }
        }

        return $CanAddTagForType;
    }

    /**
     *
     *
     * @param $TagsString
     * @return array
     */
    public static function splitTags($TagsString) {
        $Tags = preg_split('`[,]`', $TagsString);
        // Trim each tag.
        foreach ($Tags as $Index => $Tag) {
            $Tag = trim($Tag);
            if (!$Tag) {
                unset($Tags[$Index]);
            } else {
                $Tags[$Index] = $Tag;
            }
        }
        $Tags = array_unique($Tags);
        return $Tags;
    }

    /**
     *
     *
     * @param $Str
     * @return string
     */
    public static function tagSlug($Str) {
        return rawurldecode(Gdn_Format::url($Str));
    }
}
