<?php
/**
 * Promoted Content module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0.17.9
 */

/**
 * Renders "Promoted" discussions and comments according to criteria.
 *
 *  - Posted by a certain role
 *  - Reached bestof status
 *  - Latest from a certain category
 */
class PromotedContentModule extends Gdn_Module {

    /** @var integer Max number of records to be fetched. */
    const MAX_LIMIT = 50;

    /**
     * @var string How should we choose the content?
     *  - role        Author's Role
     *  - rank        Author's Rank
     *  - category    Content's Category
     *  - score       Content's Score
     *  - promoted
     */
    public $Selector;

    /** @var string|int Parameters for the selector method. */
    public $Selection;

    /** @var string What type of content to fetch. One of: all, discussions, comments. */
    public $ContentType = 'all';

    /** @var integer How much content should be fetched. */
    public $Limit = 9;

    /** @var integer How often should we encapsulate content in groups. Groups of: n. */
    public $Group = 3;

    /** @var integer How many chars of Title to return. */
    public $TitleLimit = 0;

    /** @var integer How many chars of Body to return. */
    public $BodyLimit = 0;

    /** @var integer How long do we cache in seconds. */
    public $Expiry = 60;

    /** @var array Whitelist of accepted parameters. */
    public $Properties = array(
        'Selector',
        'Selection',
        'ContentType',
        'Limit',
        'Group',
        'TitleLimit',
        'BodyLimit',
        'Expiry'
    );

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'vanilla';
    }

    /**
     * Set class properties.
     *
     * @param array $Parameters Use lowercase key names that map to class properties.
     */
    public function load($Parameters = array()) {
        $Result = $this->validate($Parameters);
        if ($Result === true) {
            // Match existing properties to validates parameters.
            foreach ($this->Properties as $Property) {
                $key = strtolower($Property);
                if (isset($Parameters[$key])) {
                    $this->$Property = $Parameters[$key];
                }
            }
            if (isset($Parameters['limit'])) {
                $this->Limit = min($this->Limit, self::MAX_LIMIT);
            }
            return true;
        } else {
            // Error messages.
            return $Result;
        }
    }

    /**
     * Validate data to be used as class properties.
     *
     * @param array $Parameters .
     * @return string|true True on success or string (message) on error.
     */
    public function validate($Parameters = array()) {
        $validation = new Gdn_Validation();

        // Validate integer properties.
        $validation->applyRule('expiry', 'Integer');
        $validation->applyRule('limit', 'Integer');
        $validation->applyRule('bodylimit', 'Integer');
        $validation->applyRule('titlelimit', 'Integer');
        $validation->applyRule('group', 'Integer');

        // Validate selection.
        $validation->applyRule('selection', 'String');

        // Validate selector.
        $validation->applyRule('selector', 'Required');
        $selectorWhitelist = array('role', 'rank', 'category', 'score', 'promoted');
        if (isset($Parameters['selector']) && !in_array($Parameters['selector'], $selectorWhitelist)) {
            $validation->addValidationResult('selector', 'Invalid selector.');
        }

        // Validate ContentType.
        $typeWhitelist = array('all', 'discussions', 'comments');
        if (isset($Parameters['contenttype']) && !in_array($Parameters['contenttype'], $typeWhitelist)) {
            $validation->addValidationResult('contenttype', 'Invalid contenttype.');
        }

        $result = $validation->validate($Parameters);
        return ($result === true) ? true : $validation->resultsText();
    }

    /**
     * Get data based on class properties.
     */
    public function getData() {
        $this->setData('Content', false);
        $SelectorMethod = 'SelectBy'.ucfirst($this->Selector);
        if (method_exists($this, $SelectorMethod)) {
            $this->setData('Content', call_user_func(array($this, $SelectorMethod), $this->Selection));
        } else {
            $this->fireEvent($SelectorMethod);
        }
    }

    /**
     * Select content based on author RoleID.
     *
     * @param array|int $Parameters
     * @return array|false
     */
    protected function selectByRole($Parameters) {
        if (!is_array($Parameters)) {
            $RoleID = $Parameters;
        } else {
            $RoleID = val('RoleID', $Parameters, null);
        }

        // Lookup role name -> roleID
        if ($RoleID && is_string($RoleID)) {
            $RoleModel = new RoleModel();
            $Roles = explode(',', $RoleID);
            $RoleID = array();
            foreach ($Roles as $TestRoleID) {
                $TestRoleID = trim($TestRoleID);
                $Role = $RoleModel->GetByName($TestRoleID);
                if (!$Role) {
                    continue;
                } else {
                    $Role = array_shift($Role);
                    $RoleID[] = val('RoleID', $Role);
                }
            }
        }

        if (empty($RoleID) || !sizeof($RoleID)) {
            return false;
        }

        // Check cache
        sort($RoleID);
        $RoleIDKey = implode('-', $RoleID);
        $SelectorRoleCacheKey = "modules.promotedcontent.role.{$RoleIDKey}";
        $Content = Gdn::cache()->get($SelectorRoleCacheKey);

        if ($Content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get everyone with this Role
            $UserIDs = Gdn::sql()->select('ur.UserID')
                ->from('UserRole ur')
                ->where('ur.RoleID', $RoleID)
                ->groupBy('UserID')
                ->get()->result(DATASET_TYPE_ARRAY);
            $UserIDs = consolidateArrayValuesByKey($UserIDs, 'UserID');

            // Get matching Discussions
            $Discussions = array();
            if ($this->ShowDiscussions()) {
                $Discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->whereIn('d.InsertUserID', $UserIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $Comments = array();
            if ($this->ShowComments()) {
                $Comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->whereIn('InsertUserID', $UserIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);

                $this->JoinCategory($Comments);
            }

            // Interleave
            $Content = $this->Union('DateInserted', array(
                'Discussion' => $Discussions,
                'Comment' => $Comments
            ));
            $this->Prepare($Content);

            // Add result to cache
            Gdn::cache()->store($SelectorRoleCacheKey, $Content, array(
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ));
        }

        $this->Security($Content);
        $this->Condense($Content, $this->Limit);
        return $Content;
    }

    /**
     * Select content based on author RankID.
     *
     * @param array|int $Parameters
     * @return array|false
     */
    protected function selectByRank($Parameters) {
        // Must have Ranks enabled.
        if (!class_exists('RankModel')) {
            return false;
        }

        if (!is_array($Parameters)) {
            $RankID = $Parameters;
        } else {
            $RankID = val('RankID', $Parameters, null);
        }

        // Check for Rank passed by name.
        if (!is_numeric($RankID)) {
            $RankModel = new RankModel();
            $Rank = $RankModel->getWhere(array('Name' => $RankID))->firstRow();
            $RankID = val('RankID', $Rank);
        }

        // Disallow blank or multiple ranks.
        if (!$RankID || is_array($RankID)) {
            return false;
        }

        // Check cache
        $SelectorRankCacheKey = "modules.promotedcontent.rank.{$RankID}";
        $Content = Gdn::cache()->get($SelectorRankCacheKey);

        if ($Content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get everyone with this Role
            $UserIDs = Gdn::sql()->select('u.UserID')
                ->from('User u')
                ->where('u.RankID', $RankID)
                ->groupBy('UserID')
                ->get()->result(DATASET_TYPE_ARRAY);
            $UserIDs = consolidateArrayValuesByKey($UserIDs, 'UserID');

            // Get matching Discussions
            $Discussions = array();
            if ($this->ShowDiscussions()) {
                $Discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->whereIn('d.InsertUserID', $UserIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $Comments = array();
            if ($this->ShowComments()) {
                $Comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->whereIn('InsertUserID', $UserIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);

                $this->JoinCategory($Comments);
            }

            // Interleave
            $Content = $this->Union('DateInserted', array(
                'Discussion' => $Discussions,
                'Comment' => $Comments
            ));
            $this->Prepare($Content);

            // Add result to cache
            Gdn::cache()->store($SelectorRankCacheKey, $Content, array(
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ));
        }

        $this->Security($Content);
        $this->Condense($Content, $this->Limit);
        return $Content;
    }

    /**
     * Select content based on its CategoryID.
     *
     * @param array|int $Parameters
     * @return array|false
     */
    protected function selectByCategory($Parameters) {
        if (!is_array($Parameters)) {
            $CategoryID = $Parameters;
        } else {
            $CategoryID = val('CategoryID', $Parameters, null);
        }

        // Allow category names, and validate category exists.
        $Category = CategoryModel::categories($CategoryID);
        $CategoryID = val('CategoryID', $Category);

        // Disallow invalid or multiple categories.
        if (!$CategoryID || is_array($CategoryID)) {
            return false;
        }

        // Check cache
        $SelectorCategoryCacheKey = "modules.promotedcontent.category.{$CategoryID}";
        $Content = Gdn::cache()->get($SelectorCategoryCacheKey);

        if ($Content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get matching Discussions
            $Discussions = array();
            if ($this->ShowDiscussions()) {
                $Discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->where('d.CategoryID', $CategoryID)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $Comments = array();
            if ($this->ShowComments()) {
                $CommentDiscussionIDs = Gdn::sql()->select('d.DiscussionID')
                    ->from('Discussion d')
                    ->where('d.CategoryID', $CategoryID)
                    ->orderBy('DateLastComment', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
                $CommentDiscussionIDs = array_column($CommentDiscussionIDs, 'DiscussionID');

                $Comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->whereIn('DiscussionID', $CommentDiscussionIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);

                $this->JoinCategory($Comments);
            }

            // Interleave
            $Content = $this->Union('DateInserted', array(
                'Discussion' => $Discussions,
                'Comment' => $Comments
            ));
            $this->Prepare($Content);

            // Add result to cache
            Gdn::cache()->store($SelectorCategoryCacheKey, $Content, array(
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ));
        }

        $this->Security($Content);
        $this->Condense($Content, $this->Limit);
        return $Content;
    }

    /**
     * Select content based on its Score.
     *
     * @param array|int $Parameters
     * @return array|false
     */
    protected function selectByScore($Parameters) {
        if (!is_array($Parameters)) {
            $MinScore = $Parameters;
        } else {
            $MinScore = val('Score', $Parameters, null);
        }

        if (!is_integer($MinScore)) {
            $MinScore = false;
        }

        // Check cache
        $SelectorScoreCacheKey = "modules.promotedcontent.score.{$MinScore}";
        $Content = Gdn::cache()->get($SelectorScoreCacheKey);

        if ($Content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get matching Discussions
            $Discussions = array();
            if ($this->ShowDiscussions()) {
                $Discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit);
                if ($MinScore !== false) {
                    $Discussions->where('Score >', $MinScore);
                }
                $Discussions = $Discussions->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $Comments = array();
            if ($this->ShowComments()) {
                $Comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit);
                if ($MinScore !== false) {
                    $Comments->where('Score >', $MinScore);
                }
                $Comments = $Comments->get()->result(DATASET_TYPE_ARRAY);

                $this->JoinCategory($Comments);
            }

            // Interleave
            $Content = $this->Union('DateInserted', array(
                'Discussion' => $Discussions,
                'Comment' => $Comments
            ));
            $this->Prepare($Content);

            // Add result to cache
            Gdn::cache()->store($SelectorScoreCacheKey, $Content, array(
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ));
        }

        $this->Security($Content);
        $this->Condense($Content, $this->Limit);
        return $Content;
    }

    /**
     * Selected content that passed the Promoted threshold.
     *
     * This uses the Reactions caching system & options.
     *
     * @param array $Parameters Not used.
     * @return array|false $Content
     */
    protected function selectByPromoted($Parameters) {
        if (!class_exists('ReactionModel')) {
            return;
        }

        $RecordTypes = array();
        if ($this->ShowDiscussions()) {
            $RecordTypes[] = 'Discussion';
        }
        if ($this->ShowComments()) {
            $RecordTypes[] = 'Comment';
        }

        $ReactionModel = new ReactionModel();
        $PromotedTagID = $ReactionModel->DefineTag('Promoted', 'BestOf');
        $Content = $ReactionModel->GetRecordsWhere(
            array('TagID' => $PromotedTagID, 'RecordType' => $RecordTypes),
            'DateInserted',
            'desc',
            $this->Limit
        );

        $this->Prepare($Content);

        return $Content;
    }

    /**
     * Attach CategoryID to Comments
     *
     * @param array $Comments
     */
    protected function joinCategory(&$Comments) {
        $DiscussionIDs = array();

        foreach ($Comments as &$Comment) {
            $DiscussionIDs[$Comment['DiscussionID']] = true;
        }
        $DiscussionIDs = array_keys($DiscussionIDs);

        $Discussions = Gdn::sql()->select('d.*')
            ->from('Discussion d')
            ->whereIn('DiscussionID', $DiscussionIDs)
            ->get()->result(DATASET_TYPE_ARRAY);

        $DiscussionsByID = array();
        foreach ($Discussions as $Discussion) {
            $DiscussionsByID[$Discussion['DiscussionID']] = $Discussion;
        }
        unset($Discussions);

        foreach ($Comments as &$Comment) {
            $Comment['Discussion'] = $DiscussionsByID[$Comment['DiscussionID']];
            $Comment['CategoryID'] = valr('Discussion.CategoryID', $Comment);
        }
    }

    /**
     * Interleave two or more result arrays by a common field
     *
     * @param string $Field
     * @param array $Sections Array of result arrays
     * @return array
     */
    protected function union($Field, $Sections) {
        if (!is_array($Sections)) {
            return;
        }

        $Interleaved = array();
        foreach ($Sections as $SectionType => $Section) {
            if (!is_array($Section)) {
                continue;
            }

            foreach ($Section as $Item) {
                $ItemField = val($Field, $Item);
                $Interleaved[$ItemField] = array_merge($Item, array('RecordType' => $SectionType));

                ksort($Interleaved);
            }
        }

        $Interleaved = array_reverse($Interleaved);
        return array_values($Interleaved);
    }

    /**
     * Pre-process content into a uniform format for output
     *
     * @param Array $content By reference
     */
    protected function prepare(&$content) {

        foreach ($content as &$item) {
            $contentType = val('RecordType', $item);
            $userID = val('InsertUserID', $item);
            $itemProperties = array();
            $itemFields = array('DiscussionID', 'DateInserted', 'DateUpdated', 'Body', 'Format', 'RecordType', 'Url', 'CategoryID', 'CategoryName', 'CategoryUrl',);

            switch (strtolower($contentType)) {
                case 'comment':
                    $itemFields = array_merge($itemFields, array('CommentID'));

                    // Comment specific
                    $itemProperties['Name'] = sprintf(t('Re: %s'), valr('Discussion.Name', $item, val('Name', $item)));
                    $url = CommentUrl($item);
                    break;

                case 'discussion':
                    $itemFields = array_merge($itemFields, array('Name', 'Type'));
                    $url = DiscussionUrl($item);
                    break;
            }

            $item['Url'] = $url;
            if ($categoryId = val('CategoryID', $item)) {
                $category = CategoryModel::categories($categoryId);
                $item['CategoryName'] = val('Name', $category);
                $item['CategoryUrl'] = CategoryUrl($category);
            }
            $itemFields = array_fill_keys($itemFields, true);
            $filteredItem = array_intersect_key($item, $itemFields);
            $itemProperties = array_merge($itemProperties, $filteredItem);
            $item = $itemProperties;

            // Attach User
            $userFields = array('UserID', 'Name', 'Title', 'Location', 'PhotoUrl', 'RankName', 'Url', 'Roles', 'RoleNames');

            $user = Gdn::userModel()->getID($userID);
            $roleModel = new RoleModel();
            $roles = $roleModel->GetByUserID($userID)->resultArray();
            $roleNames = '';
            foreach ($roles as $role) {
                $roleNames[] = val('Name', $role);
            }
            // check
            $rankName = null;
            if (class_exists('RankModel')) {
                $rankName = val('Name', RankModel::Ranks(val('RankID', $user)), null);
            }
            $userProperties = array(
                'Url' => url(userUrl($user), true),
                'PhotoUrl' => UserPhotoUrl($user),
                'RankName' => $rankName,
                'RoleNames' => $roleNames,
                'CssClass' => val('_CssClass', $user)
            );
            $user = (array)$user;
            $userFields = array_fill_keys($userFields, true);
            $filteredUser = array_intersect_key($user, $userFields);
            $userProperties = array_merge($filteredUser, $userProperties);
            $item['Author'] = $userProperties;
        }
    }

    /**
     * Strip out content that this user is not allowed to see
     *
     * @param array $Content Content array, by reference
     */
    protected function security(&$Content) {
        if (!is_array($Content)) {
            return;
        }
        $Content = array_filter($Content, array($this, 'SecurityFilter'));
    }

    /**
     * Determine if we have permission to view this content.
     *
     * @param $ContentItem
     * @return bool
     */
    protected function securityFilter($ContentItem) {
        $CategoryID = val('CategoryID', $ContentItem, null);
        if (is_null($CategoryID) || $CategoryID === false) {
            return false;
        }

        $Category = CategoryModel::categories($CategoryID);
        $CanView = val('PermsDiscussionsView', $Category);
        if (!$CanView) {
            return false;
        }

        return true;
    }

    /**
     * Condense an interleaved content list down to the required size
     *
     * @param array $Content
     * @param array $Limit
     */
    protected function condense(&$Content, $Limit) {
        $Content = array_slice($Content, 0, $Limit);
    }

    /**
     * Whether to display promoted comments.
     *
     * @return bool
     */
    public function showComments() {
        return ($this->ContentType == 'all' || $this->ContentType == 'comments') ? true : false;
    }

    /**
     * Whether to display promoted discussions.
     *
     * @return bool
     */
    public function showDiscussions() {
        return ($this->ContentType == 'all' || $this->ContentType == 'discussions') ? true : false;
    }

    /**
     * Default asset target for this module.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     * Render.
     *
     * @return string
     */
    public function toString() {
        if ($this->data('Content', null) == null) {
            $this->GetData();
        }

        return parent::ToString();
    }
}
