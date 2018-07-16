<?php
/**
 * Promoted Content module
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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

    /** @var bool Whether or not to hide module if no results are found. */
    private $ShowIfNoResults = true;

    /** @var integer How many chars of Title to return. */
    public $TitleLimit = 0;

    /** @var integer How many chars of Body to return. */
    public $BodyLimit = 0;

    /** @var integer How long do we cache in seconds. */
    public $Expiry = 60;

    /** @var array Whitelist of accepted parameters. */
    public $Properties = [
        'Selector',
        'Selection',
        'ContentType',
        'Limit',
        'Group',
        'TitleLimit',
        'BodyLimit',
        'Expiry'
    ];

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'vanilla';
    }

    /**
     * Set class properties.
     *
     * @param array $parameters Use lowercase key names that map to class properties.
     */
    public function load($parameters = []) {
        $result = $this->validate($parameters);
        if ($result === true) {
            // Match existing properties to validates parameters.
            foreach ($this->Properties as $property) {
                $key = strtolower($property);
                if (isset($parameters[$key])) {
                    $this->$property = $parameters[$key];
                }
            }
            if (isset($parameters['limit'])) {
                $this->Limit = min($this->Limit, self::MAX_LIMIT);
            }
            return true;
        } else {
            // Error messages.
            return $result;
        }
    }

    /**
     * Set ShowIfNoResults
     *
     * @param bool $val
     */
    public function setShowIfNoResults($val) {
        $this->ShowIfNoResults = filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Validate data to be used as class properties.
     *
     * @param array $parameters .
     * @return string|true True on success or string (message) on error.
     */
    public function validate($parameters = []) {
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
        $selectorWhitelist = ['role', 'rank', 'category', 'score', 'promoted'];
        if (isset($parameters['selector']) && !in_array($parameters['selector'], $selectorWhitelist)) {
            $validation->addValidationResult('selector', 'Invalid selector.');
        }

        // Validate ContentType.
        $typeWhitelist = ['all', 'discussions', 'comments'];
        if (isset($parameters['contenttype']) && !in_array($parameters['contenttype'], $typeWhitelist)) {
            $validation->addValidationResult('contenttype', 'Invalid contenttype.');
        }

        // Results
        $result = $validation->validate($parameters);
        return ($result === true) ? true : $validation->resultsText();
    }

    /**
     * Get ShowIfNoResults
     *
     * @return bool
     */
    public function getShowIfNoResults() {
        return $this->ShowIfNoResults;
    }


    /**
     * Get data based on class properties.
     */
    public function getData() {
        $this->setData('Content', false);
        $selectorMethod = 'SelectBy'.ucfirst($this->Selector);
        if (method_exists($this, $selectorMethod)) {
            $this->setData('Content', call_user_func([$this, $selectorMethod], $this->Selection));
        } else {
            $this->fireEvent($selectorMethod);
        }
    }

    /**
     * Select content based on author RoleID.
     *
     * @param array|int $parameters
     * @return array|false
     */
    protected function selectByRole($parameters) {
        if (!is_array($parameters)) {
            $roleID = $parameters;
        } else {
            $roleID = val('RoleID', $parameters, null);
        }

        // Lookup role name -> roleID
        if ($roleID && is_string($roleID)) {
            $roleModel = new RoleModel();
            $roles = explode(',', $roleID);
            $roleID = [];
            foreach ($roles as $testRoleID) {
                $testRoleID = trim($testRoleID);
                $role = $roleModel->getByName($testRoleID);
                if (!$role) {
                    continue;
                } else {
                    $role = array_shift($role);
                    $roleID[] = val('RoleID', $role);
                }
            }
        }

        if (empty($roleID) || !sizeof($roleID)) {
            return false;
        }

        // Check cache
        sort($roleID);
        $roleIDKey = implode('-', $roleID);
        $selectorRoleCacheKey = "modules.promotedcontent.role.{$roleIDKey}";
        $content = Gdn::cache()->get($selectorRoleCacheKey);

        if ($content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get everyone with this Role
            $userIDs = Gdn::sql()->select('ur.UserID')
                ->from('UserRole ur')
                ->where('ur.RoleID', $roleID)
                ->groupBy('UserID')
                ->get()->result(DATASET_TYPE_ARRAY);
            $userIDs = array_column($userIDs, 'UserID');

            // Get matching Discussions
            $discussions = [];
            if ($this->showDiscussions()) {
                $discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->whereIn('d.InsertUserID', $userIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $comments = [];
            if ($this->showComments()) {
                $comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->whereIn('InsertUserID', $userIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);

                $this->joinCategory($comments);
            }

            // Interleave
            $content = $this->union('DateInserted', [
                'Discussion' => $discussions,
                'Comment' => $comments
            ]);
            $this->processContent($content);

            // Add result to cache
            Gdn::cache()->store($selectorRoleCacheKey, $content, [
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ]);
        }

        $this->security($content);
        $this->condense($content, $this->Limit);
        return $content;
    }

    /**
     * Select content based on author RankID.
     *
     * @param array|int $parameters
     * @return array|false
     */
    protected function selectByRank($parameters) {
        // Must have Ranks enabled.
        if (!class_exists('RankModel')) {
            return false;
        }

        if (!is_array($parameters)) {
            $rankID = $parameters;
        } else {
            $rankID = val('RankID', $parameters, null);
        }

        // Check for Rank passed by name.
        if (!is_numeric($rankID)) {
            $rankModel = new RankModel();
            $rank = $rankModel->getWhere(['Name' => $rankID])->firstRow();
            $rankID = val('RankID', $rank);
        }

        // Disallow blank or multiple ranks.
        if (!$rankID || is_array($rankID)) {
            return false;
        }

        // Check cache
        $selectorRankCacheKey = "modules.promotedcontent.rank.{$rankID}";
        $content = Gdn::cache()->get($selectorRankCacheKey);

        if ($content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get everyone with this Role
            $userIDs = Gdn::sql()->select('u.UserID')
                ->from('User u')
                ->where('u.RankID', $rankID)
                ->groupBy('UserID')
                ->get()->result(DATASET_TYPE_ARRAY);
            $userIDs = array_column($userIDs, 'UserID');

            // Get matching Discussions
            $discussions = [];
            if ($this->showDiscussions()) {
                $discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->whereIn('d.InsertUserID', $userIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $comments = [];
            if ($this->showComments()) {
                $comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->whereIn('InsertUserID', $userIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);

                $this->joinCategory($comments);
            }

            // Interleave
            $content = $this->union('DateInserted', [
                'Discussion' => $discussions,
                'Comment' => $comments
            ]);
            $this->processContent($content);

            // Add result to cache
            Gdn::cache()->store($selectorRankCacheKey, $content, [
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ]);
        }

        $this->security($content);
        $this->condense($content, $this->Limit);
        return $content;
    }

    /**
     * Select content based on its CategoryID.
     *
     * @param array|int $parameters
     * @return array|false
     */
    protected function selectByCategory($parameters) {
        if (!is_array($parameters)) {
            $categoryID = $parameters;
        } else {
            $categoryID = val('CategoryID', $parameters, null);
        }

        // Allow category names, and validate category exists.
        $category = CategoryModel::categories($categoryID);
        $categoryID = val('CategoryID', $category);

        // Disallow invalid or multiple categories.
        if (!$categoryID || is_array($categoryID)) {
            return false;
        }

        // Check cache
        $selectorCategoryCacheKey = "modules.promotedcontent.category.{$categoryID}";
        $content = Gdn::cache()->get($selectorCategoryCacheKey);

        if ($content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get matching Discussions
            $discussions = [];
            if ($this->showDiscussions()) {
                $discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->where('d.CategoryID', $categoryID)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $comments = [];
            if ($this->showComments()) {
                $commentDiscussionIDs = Gdn::sql()->select('d.DiscussionID')
                    ->from('Discussion d')
                    ->where('d.CategoryID', $categoryID)
                    ->orderBy('DateLastComment', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);
                $commentDiscussionIDs = array_column($commentDiscussionIDs, 'DiscussionID');

                $comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->whereIn('DiscussionID', $commentDiscussionIDs)
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit)
                    ->get()->result(DATASET_TYPE_ARRAY);

                $this->joinCategory($comments);
            }

            // Interleave
            $content = $this->union('DateInserted', [
                'Discussion' => $discussions,
                'Comment' => $comments
            ]);
            $this->processContent($content);

            // Add result to cache
            Gdn::cache()->store($selectorCategoryCacheKey, $content, [
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ]);
        }

        $this->security($content);
        $this->condense($content, $this->Limit);
        return $content;
    }

    /**
     * Select content based on its Score.
     *
     * @param array|int $parameters
     * @return array|false
     */
    protected function selectByScore($parameters) {
        if (!is_array($parameters)) {
            $minScore = $parameters;
        } else {
            $minScore = val('Score', $parameters, null);
        }

        $minScore = filter_var($minScore, FILTER_VALIDATE_INT);
        // Check cache
        $selectorScoreCacheKey = "modules.promotedcontent.score.{$minScore}";
        $content = Gdn::cache()->get($selectorScoreCacheKey);

        if ($content == Gdn_Cache::CACHEOP_FAILURE) {
            // Get matching Discussions
            $discussions = [];
            if ($this->showDiscussions()) {
                $discussions = Gdn::sql()->select('d.*')
                    ->from('Discussion d')
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit);
                if ($minScore !== false) {
                    $discussions->where('Score >', $minScore);
                }
                $discussions = $discussions->get()->result(DATASET_TYPE_ARRAY);
            }

            // Get matching Comments
            $comments = [];
            if ($this->showComments()) {
                $comments = Gdn::sql()->select('c.*')
                    ->from('Comment c')
                    ->orderBy('DateInserted', 'DESC')
                    ->limit($this->Limit);
                if ($minScore !== false) {
                    $comments->where('Score >', $minScore);
                }
                $comments = $comments->get()->result(DATASET_TYPE_ARRAY);

                $this->joinCategory($comments);
            }

            // Interleave
            $content = $this->union('DateInserted', [
                'Discussion' => $discussions,
                'Comment' => $comments
            ]);
            $this->processContent($content);

            // Add result to cache
            Gdn::cache()->store($selectorScoreCacheKey, $content, [
                Gdn_Cache::FEATURE_EXPIRY => $this->Expiry
            ]);
        }

        $this->security($content);
        $this->condense($content, $this->Limit);
        return $content;
    }

    /**
     * Selected content that passed the Promoted threshold.
     *
     * This uses the Reactions caching system & options.
     *
     * @param array $parameters Not used.
     * @return array|false $content
     */
    protected function selectByPromoted($parameters) {
        if (!class_exists('ReactionModel')) {
            return;
        }

        $recordTypes = [];
        if ($this->showDiscussions()) {
            $recordTypes[] = 'Discussion';
        }
        if ($this->showComments()) {
            $recordTypes[] = 'Comment';
        }

        $reactionModel = new ReactionModel();
        $promotedTagID = $reactionModel->defineTag('Promoted', 'BestOf');
        $content = $reactionModel->getRecordsWhere(
            ['TagID' => $promotedTagID, 'RecordType' => $recordTypes],
            'DateInserted',
            'desc',
            $this->Limit
        );

        $this->processContent($content);

        return $content;
    }

    /**
     * Attach CategoryID to Comments
     *
     * @param array $comments
     */
    protected function joinCategory(&$comments) {
        $discussionIDs = [];

        foreach ($comments as &$comment) {
            $discussionIDs[$comment['DiscussionID']] = true;
        }
        $discussionIDs = array_keys($discussionIDs);

        $discussions = Gdn::sql()->select('d.*')
            ->from('Discussion d')
            ->whereIn('DiscussionID', $discussionIDs)
            ->get()->result(DATASET_TYPE_ARRAY);

        $discussionsByID = [];
        foreach ($discussions as $discussion) {
            $discussionsByID[$discussion['DiscussionID']] = $discussion;
        }
        unset($discussions);

        foreach ($comments as &$comment) {
            $comment['Discussion'] = $discussionsByID[$comment['DiscussionID']];
            $comment['CategoryID'] = valr('Discussion.CategoryID', $comment);
        }
    }

    /**
     * Interleave two or more result arrays by a common field
     *
     * @param string $field
     * @param array $sections Array of result arrays
     * @return array
     */
    protected function union($field, $sections) {
        if (!is_array($sections)) {
            return;
        }

        $interleaved = [];
        foreach ($sections as $sectionType => $section) {
            if (!is_array($section)) {
                continue;
            }

            foreach ($section as $item) {
                $itemField = val($field, $item);
                $interleaved[$itemField] = array_merge($item, ['RecordType' => $sectionType]);

                ksort($interleaved);
            }
        }

        $interleaved = array_reverse($interleaved);
        return array_values($interleaved);
    }

    /**
     * Pre-process content into a uniform format for output
     *
     * @param Array $content By reference
     */
    protected function processContent(&$content) {

        foreach ($content as &$item) {
            $contentType = val('RecordType', $item);
            $userID = val('InsertUserID', $item);
            $itemProperties = [];
            $itemFields = ['DiscussionID', 'DateInserted', 'DateUpdated', 'Body', 'Format', 'RecordType', 'Url', 'CategoryID', 'CategoryName', 'CategoryUrl',];

            switch (strtolower($contentType)) {
                case 'comment':
                    $itemFields = array_merge($itemFields, ['CommentID']);

                    // Comment specific
                    $itemProperties['Name'] = sprintf(t('Re: %s'), valr('Discussion.Name', $item, val('Name', $item)));
                    $url = commentUrl($item);
                    break;

                case 'discussion':
                    $itemFields = array_merge($itemFields, ['Name', 'Type']);
                    $url = discussionUrl($item);
                    break;
            }

            $item['Url'] = $url;
            if ($categoryId = val('CategoryID', $item)) {
                $category = CategoryModel::categories($categoryId);
                $item['CategoryName'] = val('Name', $category);
                $item['CategoryUrl'] = categoryUrl($category);
            }
            $itemFields = array_fill_keys($itemFields, true);
            $filteredItem = array_intersect_key($item, $itemFields);
            $itemProperties = array_merge($itemProperties, $filteredItem);
            $item = $itemProperties;

            // Attach User
            $userFields = ['UserID', 'Name', 'Title', 'Location', 'PhotoUrl', 'RankName', 'Url', 'Roles', 'RoleNames'];

            $user = Gdn::userModel()->getID($userID);
            $roleModel = new RoleModel();
            $roles = $roleModel->getByUserID($userID)->resultArray();
            $roleNames = [];
            foreach ($roles as $role) {
                $roleNames[] = val('Name', $role);
            }
            // check
            $rankName = null;
            if (class_exists('RankModel')) {
                $rankName = val('Name', RankModel::ranks(val('RankID', $user)), null);
            }
            $userProperties = [
                'Url' => url(userUrl($user), true),
                'PhotoUrl' => userPhotoUrl($user),
                'RankName' => $rankName,
                'RoleNames' => $roleNames,
                'CssClass' => val('_CssClass', $user)
            ];
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
     * @param array $content Content array, by reference
     */
    protected function security(&$content) {
        if (!is_array($content)) {
            return;
        }
        $content = array_filter($content, [$this, 'SecurityFilter']);
    }

    /**
     * Determine if we have permission to view this content.
     *
     * @param $contentItem
     * @return bool
     */
    protected function securityFilter($contentItem) {
        $categoryID = val('CategoryID', $contentItem, null);
        if (is_null($categoryID) || $categoryID === false) {
            return false;
        }

        $category = CategoryModel::categories($categoryID);
        $canView = val('PermsDiscussionsView', $category);
        if (!$canView) {
            return false;
        }

        return true;
    }

    /**
     * Condense an interleaved content list down to the required size
     *
     * @param array $content
     * @param array $limit
     */
    protected function condense(&$content, $limit) {
        $content = array_slice($content, 0, $limit);
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
            $this->getData();
        }

        return parent::toString();
    }
}
