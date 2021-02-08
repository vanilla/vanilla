<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\EventManager;
use Garden\Events\ResourceEvent;
use Garden\Events\EventFromRowInterface;
use Vanilla\Addon;
use Vanilla\Reactions\Events\ReactionEvent;
use Vanilla\Utility\CamelCaseScheme;
use Garden\Schema\Schema;

/**
 * Class ReactionModel
 */
class ReactionModel extends Gdn_Model implements EventFromRowInterface {

    const USERID_SUM = 0;

    const USERID_OTHER = -1;

    const FORCE_ADD = 'add';

    const FORCE_REMOVE = 'remove';

    /** Cache grace. */
    const CACHE_GRACE = 60;

    /** @var null  */
    public static $ReactionTypes = null;

    /** @var null  */
    public static $TagIDs = null;

    /**  @var int Contains the last count from {@link getRecordsWhere()}. */
    public $LastCount;

    /** @var Gdn_SQLDriver */
    public $SQL;

    protected static $columns = ['UrlCode', 'Name', 'Description', 'Sort', 'Class', 'TagID', 'Active', 'Custom', 'Hidden'];

    /** @var EventManager */
    private $eventManager;

    /**
     * ReactionModel constructor.
     *
     * @param EventManager $eventManager
     */
    public function __construct() {
        $this->eventManager = Gdn::getContainer()->get(EventManager::class);

        parent::__construct('ReactionType');
        $this->filterFields = array_merge(
            $this->filterFields,
            ['Save' => 1]
        );
        $this->PrimaryKey = 'UrlCode';
    }

    /**
     * Clear the model static state between tests.
     */
    public static function resetForTests() {
        self::$ReactionTypes = null;
        self::$TagIDs = null;
    }

    /**
     *
     *
     * @param $name
     * @param $type
     * @param bool $oldName
     * @return bool|Gdn_DataSet|object|string
     */
    public function defineTag($name, $type, $oldName = false) {
        $row = Gdn::sql()->getWhere('Tag', ['Name' => $name])->firstRow(DATASET_TYPE_ARRAY);

        if (!$row && $oldName) {
            $row = Gdn::sql()->getWhere('Tag', ['Name' => $oldName])->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!$row) {
            $tagID = Gdn::sql()->insert('Tag',
                [
                    'Name' => $name,
                    'FullName' => $name,
                    'Type' => 'Reaction',
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime()
                ]
            );
        } else {
            $tagID = $row['TagID'];
            if ($row['Type'] != $type || $row['Name'] != $name) {
                Gdn::sql()->put('Tag',
                    ['Name' => $name, 'Type' => $type],
                    ['TagID' => $tagID]
                );
            }
        }
        return $tagID;
    }

    /**
     *
     *
     * @param $data
     * @param bool $oldCode
     */
    public function defineReactionType($data, $oldCode = false) {
        $urlCode = $data['UrlCode'];

        // Grab the tag.
        $tagID = $this->defineTag($data['UrlCode'], 'Reaction', $oldCode);
        $data['TagID'] = $tagID;

        $row = [];
        foreach (self::$columns as $column) {
            if (isset($data[$column])) {
                $row[$column] = $data[$column];
                unset($data[$column]);
            }
        }

        // Check to see if the reaction type has been customized.
        if (!isset($row['Custom'])) {

            // Get the cached result
            $current = self::reactionTypes($urlCode);
            if ($current && val('Custom', $current)) {
                return;
            }

            // Get the result from the DB
            $currentCustom = $this->SQL->getWhere('ReactionType', ['UrlCode' => $urlCode])->value('Custom');
            if ($currentCustom) {
                return;
            }
        }

        if (!empty($data)) {
            $row['Attributes'] = dbencode($data);
        }

        Gdn::sql()->replace('ReactionType', $row, ['UrlCode' => $urlCode], true);
        Gdn::cache()->remove('ReactionTypes');

        return $data;
    }

    /**
     * Get reactions on a record, given only a record type and ID.
     *
     * @param string $recordType Type of record (e.g. Discussion, Category).
     * @param int $id Unique ID of the record.
     * @param bool $restricted Filter result based on the current user's permissions.
     * @param string|null $urlCode Filter reaction results by a particular type's URL code.
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getByRecord($recordType, $id, $restricted = true, $urlCode = null, $offset = 0, $limit = null) {
        [$record, $model, $_] = $this->getRow($recordType, $id);
        $record['recordType'] = $recordType;
        $record['recordID'] = $id;

        $result = $this->getRecordReactions($record, $restricted, $urlCode, $offset, $limit);
        return $result;
    }

    /**
     * Get the reactions on a record.
     *
     * Note that in this case the record is the full database record, plus the following keys:
     *
     * - recordType: The type of the record.
     * - recordID: The ID of the record.
     *
     * @param array $record The record to get the reactions for.
     * @param bool $restricted Filter result based on the current user's permissions.
     * @param string|null $urlCode Filter reaction results by a particular type's URL code.
     * @param int $offset
     * @param int|null $limit
     * @return array
     */
    public function getRecordReactions(array $record, bool $restricted = true, ?string $urlCode = null, int $offset = 0, ?int $limit = null): array {
        if ($limit === null) {
            $limit = $this->getDefaultLimit();
        }

        $where = [
            'Class' => ['Negative', 'Positive'],
            'Active' => true
        ];
        if ($restricted === false || Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $where['Class'][] = 'Flag';
        }
        if ($urlCode !== null) {
            $where['UrlCode'] = $urlCode;
        }

        $typesWhere = $this->eventManager->fireFilter('reactionModel_getReactionTypesFilter', $where, $record);
        $types = self::getReactionTypes($typesWhere);
        $tagIDs = array_column($types, 'TagID', 'TagID');

        $rows = $this->SQL->getWhere(
            'UserTag',
            ['RecordType' => $record['recordType'], 'RecordID' => $record['recordID'], 'TagID' => $tagIDs],
            'DateInserted',
            'desc',
            $limit,
            $offset
        )->resultArray();
        Gdn::userModel()->expandUsers($rows, ['UserID']);
        array_walk($rows, function(&$row) use ($types){
            $row['ReactionType'] = self::fromTagID($row['TagID']);
        });

        return $rows;
    }

    /**
     * Get the user's reaction to a specific record.
     *
     * @param int $userID The user ID.
     * @param string $recordType Type of record (e.g. Discussion, Comment)
     * @param int $recordID Unique ID of the record.
     * @return array|bool
     */
    public function getUserReaction($userID, $recordType, $recordID) {
        $result = false;

        $tagIDs = array_column(self::reactionTypes(), 'TagID');
        $row = $this->SQL
            ->select('TagID')
            ->from('UserTag')
            ->where([
                'RecordType' => $recordType,
                'RecordID' => $recordID,
                'UserID' => $userID,
                'TagID' => $tagIDs
            ])->limit(1)
            ->get()->firstRow(DATASET_TYPE_ARRAY);
        if (!empty($row)) {
            $reactionType = self::fromTagID($row['TagID']);
            if ($reactionType) {
                $result = $reactionType;
            }
        }

        return $result;
    }

    /**
     *
     *
     * @param array $where
     * @return array
     */
    public static function getReactionTypes($where = []) {
        $types = self::reactionTypes();
        $result = [];
        foreach ($types as $index => $type) {
            if (self::filter($type, $where)) {
                // Set Attributes as fields
                $attributes = val('Attributes', $type);
                if (is_string($attributes)) {
                    $attributes = dbdecode($attributes);
                    $attributes = (is_array($attributes)) ? $attributes : [];
                    setValue('Attributes', $type, $attributes);
                }
                // Add the result
                $result[$index] = $type;
            }
        }
        return $result;
    }

    /**
     *
     *
     * @param $row
     * @param $where
     * @return bool
     */
    public static function filter($row, $where) {
        foreach ($where as $column => $value) {
            if (!isset($row[$column]) && $value) {
                return false;
            }

            $rowValue = $row[$column];
            if (is_array($value)) {
                if (!in_array($rowValue, $value)) {
                    return false;
                }
            } else {
                if ($rowValue != $value) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     *
     *
     * @param $tagID
     * @return mixed|null
     */
    public static function fromTagID($tagID) {
        if (self::$TagIDs === null) {
            $types = self::reactionTypes();
            self::$TagIDs = Gdn_DataSet::index($types, ['TagID']);

        }
        return val($tagID, self::$TagIDs);
    }

    /**
     *
     *
     * @param $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws Exception
     */
    public function getRecordsWhere($where, $orderFields = '', $orderDirection = '', $limit = 30, $offset = 0) {
        // Grab the user tags.
        $userTags = $this->buildUserTagQuery($where, $orderFields, $orderDirection, $limit, $offset)->get()->resultArray();

        $this->LastCount = count($userTags);
        self::joinRecords($userTags);

        return $userTags;
    }

    /**
     * Get all of the records a user has reacted to.
     *
     * @param int $userID
     * @param array $reactionCodes An array of the url codes of the desired reactions.
     * @param array $wheres
     * @param int|null $limit
     * @return array An array of discussionIDs
     */
    public function getReactedDiscussionIDsByUser(
        int $userID,
        array $reactionCodes = [],
        array $wheres = [],
        ?int $limit = null
    ): array {
        $reactionTagIDs = [];
        if (!empty($reactionCodes)) {
            foreach ($reactionCodes as $code) {
                $tagID = self::reactionTypes($code)['TagID'];
                if (!is_null($tagID)) {
                    $reactionTagIDs[] = $tagID;
                }
            }
        }

        $allWheres = ['UserID' => $userID, 'RecordType' => 'Discussion'];

        if (empty($reactionTagIDs)) {
            throw new \Garden\Web\Exception\NotFoundException(t('reactionType(s) not found.'));
        } else {
            $allWheres += ['TagID' => $reactionTagIDs];
        }

        $allWheres = array_merge($allWheres, $wheres);

        $query = $this->buildUserTagQuery($allWheres, '', '', $limit);
        $query->select('RecordID');
        $recordIDs = $query->get()->resultArray();

        return array_column($recordIDs, 'RecordID');
    }

    /**
     * Get a list of reactions and populate each with the total number of that reaction for the provided record.
     *
     * @param array $row A resource record (e.g. discussion, comment)
     * @param bool $restricted Filter result based on the current user's permissions.
     * @return array
     */
    public function getRecordSummary(array $row, $restricted = true) {
        $data = [];

        // Grab the reaction breakdown from the row. Make doubly sure the attribute/data is an array.
        if (array_key_exists('Data', $row)) {
            $row['Data'] = dbdecode($row['Data']);
            if (array_key_exists('React', $row['Data'])) {
                $data = $row['Data']['React'];
            }
        } elseif (array_key_exists('Attributes', $row)) {
            if (is_string($row['Attributes'])) {
                $row['Attributes'] = dbdecode($row['Attributes']);
            }
            if (isset($row['Attributes']['React'])) {
                $data = $row['Attributes']['React'];
            }
        }

        $classes = ['Positive', 'Negative'];
        if ($restricted === false || Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $classes[] = 'Flag';
        }
        $typesWhere = [
            'Class' => $classes,
            'Active' => 1
        ];
        $typesWhere = $this->eventManager->fireFilter('reactionModel_getReactionTypesFilter', $typesWhere, $row);
        $result = self::getReactionTypes($typesWhere);
        $result = array_values($result);

        foreach ($result as &$reaction) {
            if (array_key_exists($reaction['UrlCode'], $data)) {
                $count = $data[$reaction['UrlCode']];
            } else {
                $count = 0;
            }
            $reaction['Count'] = $count;
        }

        return $result;
    }

    /**
     *
     *
     * @param $type
     * @param $iD
     * @param null $operation
     * @return array
     * @throws Exception
     */
    public function getRow($type, $iD, $operation = null) {
        $attrColumn = 'Attributes';

        switch ($type) {
            case 'Comment':
                $model = new CommentModel();
                $row = $model->getID($iD, DATASET_TYPE_ARRAY);
                break;
            case 'Discussion':
                $model = new DiscussionModel();
                $row = $model->getID($iD);
                break;
            case 'Activity':
                $model = new ActivityModel();
                $row = $model->getID($iD, DATASET_TYPE_ARRAY);
                $attrColumn = 'Data';
                break;
            default:
                throw notFoundException(ucfirst($type));
        }

        $log = null;
        if (!$row && $operation) {
            // The row may have been logged so try and grab it.
            $logModel = new LogModel();
            $log = $logModel->getWhere(['RecordType' => $type, 'RecordID' => $iD, 'Operation' => $operation]);

            if (count($log) == 0) {
                throw notFoundException($type);
            }
            $log = $log[0];
            $row = $log['Data'];
        }
        $row = (array)$row;

        // Make sure the attributes are in the row and unserialized.
        $attributes = getValue($attrColumn, $row, []);
        if (is_string($attributes)) {
            $attributes = dbdecode($attributes);
        }
        if (!is_array($attributes)) {
            $attributes = [];
        }

        $row[$attrColumn] = $attributes;
        return [$row, $model, $log];
    }

    /**
     *
     *
     * @param $recordType
     * @param $recordID
     * @param $reaction
     * @param int $offset
     * @param int $limit
     * @param bool $joinUsers Should users be joined into the result?
     * @return array
     */
    public function getUsers($recordType, $recordID, $reaction, $offset = 0, $limit = 10, $joinUsers = true) {
        $reactionType = self::reactionTypes($reaction);
        if (!$reactionType) {
            return [];
        }

        $tagID = val('TagID', $reactionType);
        $userTags = $this->SQL
            ->getWhere(
                'UserTag',
                ['RecordType' => $recordType, 'RecordID' => $recordID, 'TagID' => $tagID],
                'DateInserted',
                'desc',
                $limit,
                $offset
            )
            ->resultArray();

        if ($joinUsers) {
            Gdn::userModel()->joinUsers($userTags, ['UserID']);
        }

        return $userTags;
    }

    /**
     *
     *
     * @param $data
     * @param bool $recordType
     */
    public function joinUserTags(&$data, $recordType = false) {
        if (!$data) {
            return;
        }

        $iDs = [];
        $userIDs = [];

        if ((is_object($data) && !($data instanceof Gdn_Dataset)) || (is_array($data) && !isset($data[0]))) {
            $data2 = [&$data];
        } else {
            $data2 =& $data;
        }

        foreach ($data2 as $row) {
            if (!$recordType)
                $rT = val('RecordType', $row);
            else
                $rT = $recordType;

            $iD = val($rT.'ID', $row);

            if ($iD)
                $iDs[$rT][$iD] = 1;
        }

        $tagsData = [];
        foreach ($iDs as $rT => $in) {
            $tagsData[$rT] = $this->SQL
                ->select('RecordID')
                ->select('UserID')
                ->select('TagID')
                ->select('DateInserted')
                ->from('UserTag')
                ->where('RecordType', $rT)
                ->whereIn('RecordID', array_keys($in))
                ->orderBy('DateInserted')
                ->get()->resultArray();
        }

        $tags = [];
        foreach ($tagsData as $rT => $rows) {
            foreach ($rows as $row) {
                $userIDs[$row['UserID']] = 1;
                $tags[$rT.'-'.$row['RecordID']][] = $row;
            }
        }

        // Join the tags.
        foreach ($data2 as &$row) {
            if ($recordType) {
                $rT = $recordType;
            } else {
                $rT = val('RecordType', $row);
            }
            if (!$rT) {
                $rT = 'RecordType';
            }
            $pK = $rT.'ID';
            $iD = val($pK, $row);

            if ($iD) {
                $tagRow = val($rT.'-'.$iD, $tags, []);
            } else {
                $tagRow = [];
            }

            setValue('UserTags', $row, $tagRow);
        }
    }

    /**
     * Merge user reactions for all of the users that were never merged.
     *
     * @return int
     */
    public function mergeOldUserReactions() {
        $merges = $this->SQL->getWhere('UserMerge', ['ReactionsMerged' => 0], 'DateInserted')->resultArray();

        $count = 0;
        foreach ($merges as $merge) {
            $this->mergeUsers($merge['OldUserID'], $merge['NewUserID']);
            $this->SQL->put('UserMerge', ['ReactionsMerged' => 1], ['MergeID' => $merge['MergeID']]);
            $count++;
        }
        return $count;
    }

    /**
     * Merge the reactions of two users.
     *
     * This copies the reactions from the {@link $oldUserID} to the {@link $newUserID}
     *
     * @param int $oldUserID The ID of the old user.
     * @param int $newUserID The ID of the new user.
     * @return array
     */
    public function mergeUsers($oldUserID, $newUserID) {
        $sql = $this->SQL;

        // Get all of the reactions the user has made.
        $reactions = $sql->getWhere('UserTag',
                ['UserID' => $oldUserID, 'RecordType' => ['Discussion', 'Comment', 'Activity', 'ActivityComment']]
            )->resultArray();

        // Go through the reactions and move them from the old user to the new user.
        foreach ($reactions as $reaction) {
            [$row, $model, $_] = $this->getRow($reaction['RecordType'], $reaction['RecordID']);

            // Add the reaction for the new user.
            if ($reaction['Total'] > 0) {
                $newReaction = [
                    'RecordType' => $reaction['RecordType'],
                    'RecordID' => $reaction['RecordID'],
                    'TagID' => $reaction['TagID'],
                    'UserID' => $newUserID,
                    'DateInserted' => $reaction['DateInserted']
                ];
                $this->toggleUserTag($newReaction, $row, $model, self::FORCE_ADD);
            }

            // Remove the reaction for the old user.
            $this->toggleUserTag($reaction, $row, $model, self::FORCE_REMOVE);
        }

        return $reactions;
    }

    /**
     *
     *
     * @param $data
     */
    public static function joinRecords(&$data) {
        $iDs = [];
        $allowedCats = DiscussionModel::categoryPermissions();

        if ($allowedCats === false) {
            // This user does not have permission to view anything.
            $data = [];
            return;
        }

        // Gather all of the ids to fetch.
        foreach ($data as &$row) {
            $recordType = stringEndsWith($row['RecordType'], '-Total', true, true);
            $row['RecordType'] = $recordType;
            $iD = $row['RecordID'];
            $iDs[$recordType][$iD] = $iD;
        }

        // Fetch all of the data in turn.
        $joinData = [];
        foreach ($iDs as $recordType => $recordIDs) {
            if ($recordType == 'Comment') {
                Gdn::sql()
                    ->select('d.Name, d.CategoryID')
                    ->join('Discussion d', 'd.DiscussionID = r.DiscussionID');
            }

            $rows = Gdn::sql()
                ->select('r.*')
                ->whereIn($recordType.'ID', array_values($recordIDs))
                ->get($recordType.' r')->resultArray();

            $joinData[$recordType] = Gdn_DataSet::index($rows, [$recordType.'ID']);
        }

        // Join the rows.
        $unset = [];
        foreach ($data as $index => &$row) {
            $recordType = $row['RecordType'];
            $iD = $row['RecordID'];


            if (!isset($joinData[$recordType][$iD])) {
                $unset[] = $index;
                continue; // orphaned?
            }

            $record = $joinData[$recordType][$iD];

            if ($allowedCats !== true) {
                // Check to see if the user has permission to view this record.
                $categoryID = val('CategoryID', $record, -1);
                if (!in_array($categoryID, $allowedCats)) {
                    $unset[] = $index;
                    continue;
                }
            }

            $row = array_merge($row, $record);

            switch ($recordType) {
                case 'Discussion':
                    $url = discussionUrl($row, '', '#latest');
                    break;
                case 'Comment':
                    $row['Name'] = sprintf(t('Re: %s'), $row['Name']);
                    $url = commentUrl($row);
                    break;
                default:
                    $url = '';
            }
            $row['Url'] = $url;

            // Join the category
            $category = CategoryModel::categories(val('CategoryID', $row, ''));
            $row['CategoryCssClass'] = val('CssClass', $category);
        }

        foreach ($unset as $index) {
            unset($data[$index]);
        }

        // Join the users.
        Gdn::userModel()->joinUsers($data, ['InsertUserID']);

        if (!empty($unset)) {
            $data = array_values($data);
        }
    }

    /**
     * Toggle a reaction on a record.
     *
     * @param array $data The reaction data to add. This is an array with the following keys:
     * - RecordType: The type of record (table name) being reacted to.
     * - RecordID: The primary key ID of the record being reacted to.
     * - TagID: The reaction tag to use.
     * - UserID: The user reacting.
     * - DateInserted: Optional. The date of the reaction.
     * @param array $record The record being reacted to as obtained from {@link ReactionModel::getRow()}.
     * @param Gdn_Model $model The model of the record being reacted to as obtained from {@link ReactionModel::getRow()}.
     * @param bool $delete A hint to the toggle. One of the following:
     * - ReactionModel::FORCE_ADD: Add the reaction if it does not exist. Otherwise do nothing.
     * - ReactionModel::FORCE_REMOVE: Remove the reaction if it exists. Otherwise do nothing.
     * @return mixed
     */
    public function toggleUserTag(&$data, &$record, $model, $delete = null) {
        $inc = val('Total', $data, 1);
        touchValue('Total', $data, $inc);
        touchValue('DateInserted', $data, Gdn_Format::toDateTime());
        $reactionTypes = self::reactionTypes();
        $reactionTypes = Gdn_DataSet::index($reactionTypes, ['TagID']);
        $controller = Gdn::controller();

        // See if there is already a user tag.
        $where = arrayTranslate($data, ['RecordType', 'RecordID', 'UserID']);

        $userTags = $this->SQL->getWhere('UserTag', $where)->resultArray();
        $userTags = Gdn_DataSet::index($userTags, ['TagID']);
        $insert = true;

        if (isset($userTags[$data['TagID']])) {
            // The user is toggling a tag they've already done.
            if ($delete === self::FORCE_ADD) {
                // The use is forcing a tag add so this is a no-op.
                return;
            }
            $insert = false;

            $inc = -$userTags[$data['TagID']]['Total'];
            $data['Total'] = $inc;
        }

        if ($insert && ($delete === true || $delete === self::FORCE_REMOVE)) {
            return;
        }

        $recordType = $data['RecordType'];
        $attrColumn = $recordType == 'Activity' ? 'Data' : 'Attributes';

        // Delete all of the tags.
        if (count($userTags) > 0) {
            $deleteWhere = $where;
            $deleteWhere['TagID'] = array_keys($userTags);
            $this->SQL->delete('UserTag', $deleteWhere);
        }

        if ($insert) {
            // Insert the tag.
            $this->SQL->options('Ignore', true)->insert('UserTag', $data);

            // We add the row to the usertags set, but with a negative total.
            $userTags[$data['TagID']] = $data;
            $userTags[$data['TagID']]['Total'] *= -1;
        }

        // Now we need to increment the totals.
        $px = $this->SQL->Database->DatabasePrefix;
        $sql = "insert {$px}UserTag (RecordType, RecordID, TagID, UserID, DateInserted, Total)
         values (:RecordType, :RecordID, :TagID, :UserID, :DateInserted, :Total)
         on duplicate key update Total = Total + :Total2";


        $points = 0;

        foreach ($userTags as $row) {
            $args = arrayTranslate($row, [
                'RecordType' => ':RecordType',
                'RecordID' => ':RecordID',
                'TagID' => ':TagID',
                'UserID' => ':UserID',
                'DateInserted' => ':DateInserted']
            );
            $args[':Total'] = -$row['Total'];
            $args[':Total2'] = $args[':Total'];

            // Increment the record total.
            $args[':RecordType'] = $recordType.'-Total';
            $args[':UserID'] = $record['InsertUserID'];
            $this->SQL->Database->query($sql, $args);

            // Increment the user total.
            $args[':RecordType'] = 'User';
            $args[':RecordID'] = $record['InsertUserID'];
            $args[':UserID'] = self::USERID_OTHER;
            $this->SQL->Database->query($sql, $args);

            // See what kind of points this reaction gives.
            $reactionType = $reactionTypes[$row['TagID']];
            $reactionAdded = $row['Total'] < 1;
            if ($reactionPoints = getValue('Points', $reactionType)) {
                if ($reactionAdded) {
                    $points += $reactionPoints;
                } else {
                    $points += -$reactionPoints;
                }
            }

            $reactionEvent = $this->eventFromRow(
                $row,
                $reactionAdded ? ReactionEvent::ACTION_INSERT : ReactionEvent::ACTION_DELETE
            );
            $this->getEventManager()->dispatch($reactionEvent);
        }

        // Recalculate the counts for the record.
        $totalTags = $this->SQL
            ->getWhere('UserTag', ['RecordType' => $data['RecordType'].'-Total', 'RecordID' => $data['RecordID']])
            ->resultArray();
        $totalTags = Gdn_DataSet::index($totalTags, ['TagID']);
        $react = [];
        $diffs = [];
        $set = [];

        foreach ($reactionTypes as $tagID => $type) {
            if (isset($totalTags[$tagID])) {
                $react[$type['UrlCode']] = $totalTags[$tagID]['Total'];

                if ($column = val('IncrementColumn', $type)) {
                    // This reaction type also increments a column so do that too.
                    touchValue($column, $set, 0);
                    $set[$column] += $totalTags[$tagID]['Total'] * val('IncrementValue', $type, 1);
                }
            }

            if (valr("$attrColumn.React.{$type['UrlCode']}", $record) != val($type['UrlCode'], $react)) {
                $diffs[] = $type['UrlCode'];
            }
        }

        $eventArguments = [
            'reactionTotals' => $react,
            'ReactionTypes' => &$reactionTypes,
            'Record' => $record,
            'Set' => &$set
        ];
        if (is_a($controller, 'Gdn_Controller')) {
            $controller->EventArguments += $eventArguments;
            Gdn::controller()->fireEvent('BeforeReactionsScore');
        } else {
            $this->EventArguments += $eventArguments;
            $this->fireEvent('BeforeReactionsScore');
        }

        // Send back the current scores.
        foreach ($set as $column => $value) {
            if (is_a($controller, 'Gdn_Controller')) {
                Gdn::controller()->jsonTarget("#{$recordType}_{$data['RecordID']} .Column-" . $column, self::formatScore($value), 'Html');
            }
            $record[$column] = $value;
        }

        // Send back the css class.
        [$addCss, $removeCss] = self::scoreCssClass($record, true);

        if (is_a($controller, 'Gdn_Controller')) {
            if ($removeCss) {
                Gdn::controller()->jsonTarget("#{$recordType}_{$data['RecordID']}", $removeCss, 'RemoveClass');
            }
            if ($addCss) {
                Gdn::controller()->jsonTarget("#{$recordType}_{$data['RecordID']}", $addCss, 'AddClass');
            }
            // Send back a delete for the user reaction.
            if (!$insert) {
                Gdn::controller()->jsonTarget("#{$recordType}_{$data['RecordID']} .UserReactionWrap[data-userid={$data['UserID']}]", '', 'Remove');
            }
        }

        // Kludge, add the promoted tag to promote content.
        if ($addCss == 'Promoted') {
            $promotedTagID = $this->defineTag($addCss, 'BestOf');
            $this->SQL
                ->options('Ignore', true)
                ->insert('UserTag', [
                    'RecordType' => $recordType,
                    'RecordID' => $data['RecordID'],
                    'UserID' => self::USERID_OTHER,
                    'TagID' => $promotedTagID,
                    'DateInserted' => Gdn_Format::toDateTime()
                ]);
        }

        $record[$attrColumn]['React'] = $react;
        $set[$attrColumn] = dbencode($record[$attrColumn]);

        $model->setField($data['RecordID'], $set);

        // Generate the new button for the reaction.
        if (is_a($controller, 'Gdn_Controller')) {
            Gdn::controller()->setData('Diffs', $diffs);
        }
        if (function_exists('ReactionButton')) {
            $diffs[] = 'Flag'; // always send back flag button.
            foreach ($diffs as $urlCode) {
                $button = reactionButton($record, $urlCode, ['LinkClass' => 'FlyoutButton']);
                $reactionsPlugin = ReactionsPlugin::instance();
                $reactionsPlugin->EventArguments['UrlCode'] = $urlCode;
                $reactionsPlugin->EventArguments['Record'] = $record;
                $reactionsPlugin->EventArguments['Insert'] = $insert;
                $reactionsPlugin->EventArguments['TagID'] = val('TagID', $data);
                $reactionsPlugin->EventArguments['Button'] = &$button;
                $reactionsPlugin->fireEvent('ReactionsButtonReplacement');
                if (is_a($controller, 'Gdn_Controller')) {
                    Gdn::controller()->jsonTarget("#{$recordType}_{$data['RecordID']} .ReactButton-" . $urlCode, $button, 'ReplaceWith');
                }
            }
        }

        // Give points for the reaction.
        if ($points <> 0) {
            if (method_exists('CategoryModel', 'GivePoints')) {
                $categoryID = 0;
                if (isset($record['CategoryID'])) {
                    $categoryID = $record['CategoryID'];
                } elseif (isset($record['DiscussionID'])) {
                    $categoryID = $this->SQL
                        ->getWhere('Discussion', ['DiscussionID' => $record['DiscussionID']])
                        ->value('CategoryID');
                }

                CategoryModel::givePoints($record['InsertUserID'], $points, 'Reactions', $categoryID);
            } else {
                UserModel::givePoints($record['InsertUserID'], $points, 'Reactions');
            }
        }

        return $insert;
    }

    /**
     *
     *
     * @param string $recordType
     * @param int $iD
     * @param string $reactionUrlCode
     * @param bool $selfReact Whether a user can react to their own post
     * @param string|null $force Force a reaction status. One of the FORCE_* class constants.
     */
    public function react($recordType, $iD, $reactionUrlCode, $userID = null, $selfReact = false, $force = null) {
        if (is_null($userID)) {
            $userID = Gdn::session()->UserID;
            $isModerator = checkPermission('Garden.Moderation.Manage');
            $isCurator = checkPermission('Garden.Curation.Manage');
        } else {
            $user = Gdn::userModel()->getID($userID);
            $isModerator = Gdn::userModel()->checkPermission($user, 'Garden.Moderation.Manage');
            $isCurator = Gdn::userModel()->checkPermission($user, 'Garden.Curation.Manage');
        }
        $controller = Gdn::controller();

        if (stringBeginsWith($reactionUrlCode, 'Undo-', true)) {
            $force = self::FORCE_REMOVE;
            $reactionUrlCode = stringBeginsWith($reactionUrlCode, 'Undo-', true, true);
        }
        $recordType = ucfirst($recordType);
        $reactionUrlCode = strtolower($reactionUrlCode);
        $reactionType = self::reactionTypes($reactionUrlCode);
        $attrColumn = $recordType == 'Activity' ? 'Data' : 'Attributes';

        if (!$reactionType) {
            throw notFoundException($reactionUrlCode);
        }

        $logOperation = val('Log', $reactionType);

        [$row, $model, $log] = $this->getRow($recordType, $iD, $logOperation);

        if (!$selfReact && !$isModerator && ($row['InsertUserID'] == $userID)) {
            throw new Gdn_UserException(t("You can't react to your own post."));
        }

        // Check and see if moderators are protected.
        if (val('Protected', $reactionType)) {
            $insertUser = Gdn::userModel()->getID($row['InsertUserID']);
            if (Gdn::userModel()->checkPermission($insertUser, 'Garden.Moderation.Manage')) {
                throw new Gdn_UserException(t("You can't flag a moderator's post."));
            }
        }

        // Figure out the increment.
        if ($isCurator) {
            $inc = val('ModeratorInc', $reactionType, 1);
        } else {
            $inc = 1;
        }

        // Save the user Tag.
        $data = [
            'RecordType' => $recordType,
            'RecordID' => $iD,
            'TagID' => $reactionType['TagID'],
            'UserID' => $userID,
            'Total' => $inc
        ];
        // Allow addons to validate or modify data before save.
        $data = $this->eventManager->fireFilter('reactionModel_react_saveData', $data, $this, $reactionType);

        // Create unique key based on the RecordID and UserID to limit requests on a record.
        $lockKey = 'Reactions.' . $iD . '.' . $userID;
        $haveLock = self::buildCacheLock($lockKey, self::CACHE_GRACE);

        if ($haveLock) {
            $inserted = $this->toggleUserTag($data, $row, $model, $force);
            $this->releaseCacheLock($lockKey);
        } else {
            // Fail silently because we don't have a lock, so we shouldn't execute the trailing code.
            return;
        }

        $message = [t(val('InformMessage', $reactionType, '')), 'Dismissable AutoDismiss'];

        // Now decide whether we need to log or delete the record.
        $score = valr($attrColumn.'.React.'.$reactionType['UrlCode'], $row);
        $logSet = isset($reactionType['LogThreshold']) && $reactionType['LogThreshold'] !== '';
        $removeSet = isset($reactionType['RemoveThreshold']) && $reactionType['RemoveThreshold'] !== '';
        $logThreshold = $logSet ? $reactionType['LogThreshold'] : 10000000;
        $removeThreshold = $removeSet ? $reactionType['RemoveThreshold'] : 10000000;

        if (!valr($attrColumn.'.RestoreUserID', $row) || debug()) {
            // We are only going to remove stuff if the record has not been verified.
            $log = val('Log', $reactionType, 'Moderation');

            // Do a sanity check to not delete too many comments.
            $noDelete = false;
            if ($recordType == 'Discussion' && $row['CountComments'] >= DiscussionModel::DELETE_COMMENT_THRESHOLD) {
                $noDelete = true;
            }

            $logOptions = ['GroupBy' => ['RecordID']];
            $undoButton = '';

            if ($score >= min($logThreshold, $removeThreshold)) {
                // Get all of the userIDs that flagged this.
                $otherUserData = $this->SQL
                    ->getWhere('UserTag', ['RecordType' => $recordType, 'RecordID' => $iD, 'TagID' => $reactionType['TagID']])
                    ->resultArray();
                $otherUserIDs = [];
                foreach ($otherUserData as $userRow) {
                    if ($userRow['UserID'] == $userID || !$userRow['UserID']) {
                        continue;
                    }
                    $otherUserIDs[] = $userRow['UserID'];
                }
                $logOptions['OtherUserIDs'] = $otherUserIDs;
            }

            if (!$noDelete && $score >= $removeThreshold) {
                // Remove the record to the log.
                $model->deleteID($iD, ['Log' => $log, 'LogOptions' => $logOptions]);
                $message = [
                    sprintf(t('The %s has been removed for moderation.'),
                    t($recordType)).' '.$undoButton,
                   ['CssClass' => 'Dismissable', 'id' => 'mod']
                ];
                // Send back a command to remove the row in the browser.
                if (is_a($controller, 'Gdn_Controller')) {
                    if ($recordType == 'Discussion') {
                        Gdn::controller()->jsonTarget('.ItemDiscussion', '', 'SlideUp');
                        Gdn::controller()->jsonTarget('#Content .Comments', '', 'SlideUp');
                        Gdn::controller()->jsonTarget('.CommentForm', '', 'SlideUp');
                    } else {
                        Gdn::controller()->jsonTarget("#{$recordType}_$iD", '', 'SlideUp');
                    }
                }
            } elseif ($score >= $logThreshold) {
                LogModel::insert($log, $recordType, $row, $logOptions);
                $message = [
                    sprintf(t('The %s has been flagged for moderation.'),
                    t($recordType)).' '.$undoButton,
                    ['CssClass' => 'Dismissable', 'id' => 'mod']
                ];
            }
        } else {
            if ($score >= min($logThreshold, $removeThreshold)) {
                $restoreUser = Gdn::userModel()->getID(getValueR($attrColumn.'.RestoreUserID', $row));
                $dateRestored = getValueR($attrColumn.'.DateRestored', $row);

                // The post would have been logged, but since it has been restored we won't do that again.
                $message = [
                    sprintf(t('The %s was already approved by %s on %s.'), t($recordType), userAnchor($restoreUser), Gdn_Format::dateFull($dateRestored)),
                    ['CssClass' => 'Dismissable', 'id' => 'mod']
                ];
            }
        }

        // Check to see if we need to give the user a badge.
        $this->checkBadges($row['InsertUserID'], $reactionType);

        if (is_a($controller, 'Gdn_Controller')) {
            if ($message) {
                Gdn::controller()->informMessage($message[0], $message[1]);
            }
        }

        // Clear LogCount's cache
        LogModel::clearOperationCountCache($reactionUrlCode);

        ReactionsPlugin::instance()->EventArguments = [
            'RecordType' => $recordType,
            'RecordID' => $iD,
            'Record' => $row,
            'ReactionUrlCode' => $reactionUrlCode,
            'ReactionData' => $data,
            'Insert' => $inserted,
            'UserID' => $userID
        ];
        ReactionsPlugin::instance()->fireEvent('Reaction');
    }

    /**
     * Generate an event based on a reaction log row, including an optional sender.
     *
     * @param array $row
     * @param string $action
     * @param array|null $sender
     * @return ResourceEvent
     */
    public function eventFromRow(array $row, string $action, ?array $sender = null): ResourceEvent {
        $reaction = $this->normalizeLogRow($row);
        $reaction = $this->logFragmentSchema()->validate($reaction);

        return new ReactionEvent(
            $action,
            ["reaction" => $reaction],
            $sender
        );
    }

    /**
     *
     *
     * @param $userID
     * @param $reactionType
     */
    public function checkBadges($userID, $reactionType) {
        if (!Gdn::addonManager()->isEnabled('badges', Addon::TYPE_ADDON)) {
            return;
        }

        // Get the score on the user.
        $countRow = $this->SQL->getWhere('UserTag', [
            'RecordType' => 'User',
            'RecordID' => $userID,
            'UserID' => self::USERID_OTHER,
            'TagID' => $reactionType['TagID']
        ])->firstRow(DATASET_TYPE_ARRAY);

        $score = $countRow['Total'];

        $badgeModel = new BadgeModel();
        $userBadgeModel = new UserBadgeModel();

        $badges = $badgeModel
            ->getWhere(['Type' => 'Reaction', 'Class' => $reactionType['UrlCode']], 'Threshold', 'desc')
            ->resultArray();
        foreach ($badges as $badge) {
            if ($score >= $badge['Threshold']) {
                $userBadgeModel->give($userID, $badge);
            }
        }
    }

    /**
     *
     *
     * @param null $urlCode
     * @return mixed|null
     */
    public static function reactionTypes($urlCode = null) {
        if (self::$ReactionTypes === null) {
            // Check the cache first.
            $reactionTypes = Gdn::cache()->get('ReactionTypes');

            if ($reactionTypes === Gdn_Cache::CACHEOP_FAILURE) {
                $reactionTypes = Gdn::sql()->get('ReactionType', 'Sort, Name')->resultArray();
                foreach ($reactionTypes as $type) {
                    $row = $type;
                    $attributes = dbdecode($row['Attributes']);
                    //unset($Row['Attributes']); // No! Wipes field when it's re-saved.
                    if (is_array($attributes)) {
                        foreach ($attributes as $name => $value) {
                            $row[$name] = $value;
                        }
                    }
                    self::$ReactionTypes[strtolower($row['UrlCode'])] = $row;
                }
                Gdn::cache()->store('ReactionTypes', self::$ReactionTypes);
            } else {
                self::$ReactionTypes = $reactionTypes;
            }
        }
        if ($urlCode) {
            return val(strtolower($urlCode), self::$ReactionTypes, NULL);
        }

        return self::$ReactionTypes;
    }

    /**
     * Recalculate a single records total.
     *
     * @param string|int $discussionID Identifier of the discussion.
     * @param string $type The record type.
     */
    public function recalculateRecordTotal($discussionID, $type) {
        $this->SQL
            ->whereIn('RecordType', ['Discussion-Total', 'Comment-Total'])
            ->delete('UserTag', ['RecordID' => $discussionID]);

        $sql = "insert ignore GDN_UserTag (
            RecordType,
            RecordID,
            TagID,
            UserID,
            DateInserted,
            Total
         )
         select
            '{$type}-Total',
            ut.RecordID,
            ut.TagID,
            t.InsertUserID,
            min(ut.DateInserted),
            sum(ut.Total) as SumTotal
         from GDN_UserTag ut
         join GDN_{$type} t
         	on ut.RecordID = t.{$type}ID
         where ut.RecordType = 'Discussion' and ut.RecordID = {$discussionID}
         group by
            RecordType,
            RecordID,
            TagID,
            t.InsertUserID";
        $this->SQL->query($sql);

        $this->SQL->delete('UserTag', ['UserID' => self::USERID_OTHER, 'RecordID' => $discussionID]);

        $sql = "insert ignore GDN_UserTag (
         RecordType,
         RecordID,
         TagID,
         UserID,
         DateInserted,
         Total
      )
      select
         'User',
         ut.UserID,
         ut.TagID,
         -1,
         min(ut.DateInserted),
         sum(ut.Total) as SumTotal
      from GDN_UserTag ut
      where ut.RecordType = '{$type}-Total' and ut.RecordID = {$discussionID}
      group by
         ut.UserID,
         ut.TagID";

        $this->SQL->query($sql);

        $options['recordID'] = $discussionID;
        $this->recalculateRecordCache(false, $options);
    }
    /**
     *
     *
     *
     * @throws Exception
     */
    public function recalculateTotals() {
        // Calculate all of the record totals.
        $this->SQL
            ->whereIn('RecordType', ['Discussion-Total', 'Comment-Total'])
            ->delete('UserTag');

        $recordTypes = ['Discussion', 'Comment'];
        foreach ($recordTypes as $recordType) {
            $sql = "insert ignore GDN_UserTag (
            RecordType,
            RecordID,
            TagID,
            UserID,
            DateInserted,
            Total
         )
         select
            '{$recordType}-Total',
            ut.RecordID,
            ut.TagID,
            t.InsertUserID,
            min(ut.DateInserted),
            sum(ut.Total) as SumTotal
         from GDN_UserTag ut
         join GDN_{$recordType} t
            on ut.RecordType = '{$recordType}' and ut.RecordID = {$recordType}ID
         group by
            RecordType,
            RecordID,
            TagID,
            t.InsertUserID";
            $this->SQL->query($sql);
        }

        // Calculate the user totals.
        $this->SQL->delete('UserTag', ['UserID' => self::USERID_OTHER, 'RecordType' => 'User']);

        $sql = "insert ignore GDN_UserTag (
         RecordType,
         RecordID,
         TagID,
         UserID,
         DateInserted,
         Total
      )
      select
         'User',
         ut.UserID,
         ut.TagID,
         -1,
         min(ut.DateInserted),
         sum(ut.Total) as SumTotal
      from GDN_UserTag ut
      where ut.RecordType in ('Discussion-Total', 'Comment-Total')
      group by
         ut.UserID,
         ut.TagID";
        $this->SQL->query($sql);

        // Now we need to update the caches on the individual discussion/comment rows.
        $this->recalculateRecordCache();
    }

    /**
     * Recalculate record total cache.
     *
     * @param bool $day Specific day to recalculate.
     * @param array $options Optional parameters to modify query.
     * @return int
     */
    public function recalculateRecordCache($day = FALSE, array $options = []) {
        $where = ['RecordType' => ['Discussion-Total', 'Comment-Total']];

        if (array_key_exists('recordID', $options)) {
            $where['RecordID'] = $options['recordID'];
        }


        if ($day) {
            $day = Gdn_Format::toTimestamp($day);
            $where['DateInserted >='] = gmdate('Y-m-d', $day);
            $where['DateInserted <'] = gmdate('Y-m-d', strtotime('+1 day', $day));
        }

        $totalData = $this->SQL->getWhere('UserTag',
            $where,
            'RecordType, RecordID')->resultArray();

        $react = [];
        $recordType = null;
        $recordID = null;

        $reactionTagIDs = self::reactionTypes();
        $reactionTagIDs = Gdn_DataSet::index($reactionTagIDs,['TagID']);

        $count = 0;
        foreach ($totalData as $row) {
            if (!isset($reactionTagIDs[$row['TagID']])) {
                continue;
            }

            $count++;
            $strippedRecordType = getValue(0, explode('-', $row['RecordType'], 2));
            $newRecord = $strippedRecordType != $recordType || $row['RecordID'] != $recordID;

            if ($newRecord) {
                if ($recordID) {
                    $this->_saveRecordReact($recordType, $recordID, $react);
                }

                $recordType = $strippedRecordType;
                $recordID = $row['RecordID'];
                $react = [];
            }
            $react[$reactionTagIDs[$row['TagID']]['UrlCode']] = $row['Total'];
        }

        if ($recordID) {
            $this->_saveRecordReact($recordType, $recordID, $react);
        }

        return $count;
    }

    /**
     *
     *
     * @param $recordType
     * @param $recordID
     * @param $react
     */
    protected function _saveRecordReact($recordType, $recordID, $react) {
        $set = [];
        $attrColumn = $recordType == 'Activity' ? 'Data' : 'Attributes';

        $row = $this->SQL->getWhere($recordType, [$recordType.'ID' => $recordID])->firstRow(DATASET_TYPE_ARRAY);
        $attributes = dbdecode($row[$attrColumn]);
        if (!is_array($attributes)) {
            $attributes = [];
        }

        if (empty($react)) {
            unset($attributes['React']);
        } else {
            $attributes['React'] = $react;
        }

        if (empty($attributes)) {
            $attributes = null;
        } else {
            $attributes = dbencode($attributes);
        }
        $set[$attrColumn] = $attributes;

        // Calculate the record's score too.
        foreach (self::reactionTypes() as $type) {
            if (($column = val('IncrementColumn', $type)) && isset($react[$type['UrlCode']])) {
                // This reaction type also increments a column so do that too.
                touchValue($column, $set, 0);
                $set[$column] += $react[$type['UrlCode']] * val('IncrementValue', $type, 1);
            }
        }

        // Check to see if the record is changing.
        foreach ($set as $key => $value) {
            if ($row[$key] == $value) {
                unset($set[$key]);
            }
        }

        if (!empty($set)) {
            $this->SQL->put($recordType, $set, [$recordType.'ID' => $recordID]);
        }
    }

    /**
     *
     *
     * @param $table
     * @param $set
     * @param null $key
     * @param array $dontUpdate
     * @param string $op
     */
    public function insertOrUpdate($table, $set, $key = null, $dontUpdate = [], $op = '=') {
        if ($key == null) {
            $key = $table.'ID';
        } elseif (is_numeric($key)) {
            $key = array_slice(array_keys($set), 0, $key);
        }

        $key = array_combine($key, $key);
        $dontUpdate = array_fill_keys($dontUpdate, false);

        // Make an array of the values.
        $values = array_diff_key($set, $key, $dontUpdate);

        $px = $this->SQL->Database->DatabasePrefix;
        $sql = "insert {$px}$table
            (".implode(', ', array_keys($set)).')
            values (:'.implode(', :', array_keys($set)).')
            on duplicate key update ';

        $update = '';
        foreach ($values as $key => $value) {
            if ($update) {
                $update .= ', ';
            }
            if ($op == '=') {
                $update .= "$key = :{$key}_Up";
            } else {
                $update .= "$key = $key $op :{$key}_Up";
            }
        }
        $sql .= $update;

        // Construct the arguments list.
        $args = [];
        foreach ($set as $key => $value) {
            $args[':'.$key] = $value;
        }
        foreach ($values as $key => $value) {
            $args[':'.$key.'_Up'] = $value;
        }

        // Do the final query.
        try {
            $this->SQL->Database->query($sql, $args);
        } catch (Exception $ex) {
            die();
        }
    }

    /**
     * All the score to be formatted differently.
     *
     * @param $score
     * @return int
     */
    public static function formatScore($score) {
        if (function_exists('FormatScore')) {
            return formatScore($score);
        }
        return (int)$score;
    }

    /**
     * Give the CSS class for the current score.
     *
     * @param $row
     * @param bool $all
     * @return array|string
     */
    public static function scoreCssClass($row, $all = false) {
        if (function_exists('ScoreCssClass')) {
            return scoreCssClass($row, $all);
        }

        $score = val('Score', $row);
        if (!$score) {
            $score = 0;
        }

        $bury = c('Reactions.BuryValue', -5);
        $promote = c('Reactions.PromoteValue', 5);

        if ($score <= $bury) {
            $result = $all ? 'Un-Buried' : 'Buried';
        } elseif ($score >= $promote) {
            $result = 'Promoted';
        } else {
            $result = '';
        }

        if ($all) {
            return [$result, 'Promoted Buried Un-Buried'];
        } else {
            return $result;
        }
    }

    /**
     * Save a reaction.
     *
     * @param array $formPostValues
     * @param bool $settings Unused
     * @return bool
     */
    public function save($formPostValues, $settings = false) {
        $primaryKeyValue = val($this->PrimaryKey, $formPostValues);
        $isFormValid = $this->validate($formPostValues);
        if (!$primaryKeyValue || !$isFormValid) {
            return false;
        }
        $reaction = self::reactionTypes($primaryKeyValue);
        if ($reaction) {
            // Preserve non modified attribute data
            unset($reaction['Attributes']);
            $formPostValues = array_merge($reaction, $formPostValues);
        }

        $this->defineReactionType($formPostValues);

        // Destroy the cache.
        Gdn::cache()->remove('ReactionTypes');

        return true;
    }

    /**
     * Get a schema comprised of all available fields for a reaction type.
     *
     * @return Schema
     */
    public function typeSchema(): Schema {
        static $schema;

        if ($schema === null) {
            $schema = Schema::parse([
                'urlCode:s' => 'A URL-safe identifier.',
                'name:s' => 'A user-friendly name.',
                'description:s' => 'A user-friendly description.',
                'points:i' => 'Reputation points to be applied along with this reaction.',
                'class:s|n' => 'The classification of the type. Directly maps to permissions.',
                'tagID:i' => 'The numeric ID of the tag associated with the type.',
                'attributes:o|n' => 'Metadata.',
                'sort:i|n' => 'Display order when listing types.',
                'active:b' => 'Is this type available for use?',
                'custom:b' => 'Is this a non-standard type?',
                'hidden:b' => 'Should this type be hidden from the UI?'
            ]);
        }

        return $schema;
    }

    /**
     * Get a schema fragment suitable for representing an instance of a user reaction.
     *
     * @param Schema $userFragmentSchema
     * @return Schema
     */
    public function logFragmentSchema(?Schema $userFragmentSchema = null): Schema {
        static $logFragment;

        if ($logFragment === null) {
            $logFragment = Schema::parse([
                'recordType:s',
                'recordID:i',
                'tagID:i',
                'userID:i',
                'dateInserted:dt',
                'user' => $userFragmentSchema ?? \Vanilla\Models\UserFragmentSchema::instance(),
                'reactionType' => $this->typeFragmentSchema()
            ]);
        }

        return $logFragment;
    }

    /**
     * Get a simple schema for returning a reaction.
     *
     * @return Schema
     */
    public function typeFragmentSchema(): Schema {
        static $typeFragment;

        if ($typeFragment === null) {
            $typeFragment = Schema::parse([
                'tagID:i',
                'urlcode:s',
                'name:s',
                'class:s'
            ]);
        }

        return $typeFragment;
    }

    /**
     * Normalize a reaction log row for output.
     *
     * @param array $row
     * @return array
     */
    public function normalizeLogRow(array $row) {
        $row = $this->normalizeAttributes($row);

        Gdn::userModel()->expandUsers($row, ['UserID']);
        $row['reactionType'] = $this->fromTagID($row['TagID']);

        $camelCaseSchema = new CamelCaseScheme();
        $row = $camelCaseSchema->convertArrayKeys($row);
        return $row;
    }

    /**
     * Normalize a reaction type database row for outuput.
     *
     * @param array $row
     * @return array
     */
    public function normalizeTypeRow(array $row) {
        $row = $this->normalizeAttributes($row);

        if (!array_key_exists('Points', $row)) {
            $row['Points'] = 0;
        }

        $camelCaseSchema = new CamelCaseScheme();
        $row = $camelCaseSchema->convertArrayKeys($row);
        return $row;
    }

    /**
     * Normalize the attributes column.
     *
     * @param array $row
     * @return array
     */
    private function normalizeAttributes(array $row): array {
        if (array_key_exists('Attributes', $row)) {
            if (is_string($row['Attributes'])) {
                $row['Attributes'] = dbdecode($row['Attributes']);
            }
            if ($row['Attributes'] === false) {
                $row['Attributes'] = null;
            }
        }
        return $row;
    }

    /**
     * Query the UserTag table.
     *
     * @param array $where
     * @param string|null $orderFields
     * @param string|null $orderDirection
     * @param int|null $limit
     * @param int|null $offset
     * @return object
     * @throws Exception Throws exception.
     */
    private function buildUserTagQuery(
        array $where,
        ?string $orderFields = '',
        ?string $orderDirection = '',
        ?int $limit = null,
        ?int $offset = 0
    ) {
        $userTagQuery = $this->SQL
            ->limit($limit, $offset)
            ->from('UserTag')
            ->where($where)
            ->orderBy($orderFields, $orderDirection);
        return $userTagQuery;
    }
}
