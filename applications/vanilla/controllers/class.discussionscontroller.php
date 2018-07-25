<?php
/**
 * Discussions controller
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying discussions in most contexts via /discussions endpoint.
 *
 * @todo Resolve inconsistency between use of $Page and $Offset as parameters.
 */
class DiscussionsController extends VanillaController {

    /** @var arrayModels to include. */
    public $Uses = ['Database', 'DiscussionModel', 'Form'];

    /** @var boolean Value indicating if discussion options should be displayed when rendering the discussion view.*/
    public $ShowOptions;

    /** @var object Category object. Used to limit which discussions are returned to a particular category. */
    public $Category;

    /** @var int Unique identifier for category. */
    public $CategoryID;

    /** @var array Limit the discussions to just this list of categories, checked for view permission. */
    protected $categoryIDs;

    /** @var boolean Value indicating whether to show the category following filter */
    public $enableFollowingFilter = false;

    /**
     * "Table" layout for discussions. Mimics more traditional forum discussion layout.
     *
     * @param int $page Multiplied by PerPage option to determine offset.
     */
    public function table($page = '0') {
        if ($this->SyndicationMethod == SYNDICATION_NONE) {
            $this->View = 'table';
        }
        $this->index($page);
    }

    /**
     * Default all discussions view: chronological by most recent comment.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $Page Multiplied by PerPage option to determine offset.
     */
    public function index($Page = false) {
        $this->allowJSONP(true);
        // Figure out which discussions layout to choose (Defined on "Homepage" settings page).
        $Layout = c('Vanilla.Discussions.Layout');
        switch ($Layout) {
            case 'table':
                if ($this->SyndicationMethod == SYNDICATION_NONE) {
                    $this->View = 'table';
                }
                break;
            default:
                // $this->View = 'index';
                break;
        }
        Gdn_Theme::section('DiscussionList');

        // Remove score sort
        DiscussionModel::removeSort('top');

        // Check for the feed keyword.
        if ($Page === 'feed' && $this->SyndicationMethod != SYNDICATION_NONE) {
            $Page = 'p1';
        }

        // Determine offset from $Page
        list($Offset, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30), true);
        $Page = pageNumber($Offset, $Limit);

        // Allow page manipulation
        $this->EventArguments['Page'] = &$Page;
        $this->EventArguments['Offset'] = &$Offset;
        $this->EventArguments['Limit'] = &$Limit;
        $this->fireEvent('AfterPageCalculation');

        // Set canonical URL
        $this->canonicalUrl(url(concatSep('/', 'discussions', pageNumber($Offset, $Limit, true, false)), true));

        // We want to limit the number of pages on large databases because requesting a super-high page can kill the db.
        $MaxPages = c('Vanilla.Discussions.MaxPages');
        if ($MaxPages && $Page > $MaxPages) {
            throw notFoundException();
        }

        // Setup head.
        if (!$this->data('Title')) {
            $Title = c('Garden.HomepageTitle');
            $DefaultControllerRoute = val('Destination', Gdn::router()->getRoute('DefaultController'));
            if ($Title && ($DefaultControllerRoute == 'discussions')) {
                $this->title($Title, '');
            } else {
                $this->title(t('Recent Discussions'));
            }
        }
        if (!$this->description()) {
            $this->description(c('Garden.Description', null));
        }
        if ($this->Head) {
            $this->Head->addRss(url('/discussions/feed.rss', true), $this->Head->title());
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');
        $this->addModule('TagModule');

        $this->setData('Breadcrumbs', [['Name' => t('Recent Discussions'), 'Url' => '/discussions']]);

        $categoryModel = new CategoryModel();
        $followingEnabled = $categoryModel->followingEnabled();
        if ($followingEnabled) {
            $followed = paramPreference(
                'followed',
                'FollowedDiscussions',
                'Vanilla.SaveFollowingPreference',
                null,
                Gdn::request()->get('save')
            );
            if ($this->SelfUrl === "discussions") {
                $this->enableFollowingFilter = true;
            }
        } else {
            $followed = false;
        }
        $this->setData('EnableFollowingFilter', $this->enableFollowingFilter);
        $this->setData('Followed', $followed);

        // Set criteria & get discussions data
        $this->setData('Category', false, true);
        $DiscussionModel = new DiscussionModel();
        $DiscussionModel->setSort(Gdn::request()->get());
        $DiscussionModel->setFilters(Gdn::request()->get());
        $this->setData('Sort', $DiscussionModel->getSort());
        $this->setData('Filters', $DiscussionModel->getFilters());

        // Check for individual categories.
        $categoryIDs = $this->getCategoryIDs();
        // Fix to segregate announcement conditions until announcement caching has been reworked.
        // See https://github.com/vanilla/vanilla/issues/7241
        $where = $announcementsWhere = [];
        if ($this->data('Followed')) {
            $followedCategories = array_keys($categoryModel->getFollowed(Gdn::session()->UserID));
            $visibleCategoriesResult = CategoryModel::instance()->getVisibleCategoryIDs(['filterHideDiscussions' => true]);
            if ($visibleCategoriesResult === true) {
                $visibleFollowedCategories = $followedCategories;
            } else {
                $visibleFollowedCategories = array_intersect($followedCategories, $visibleCategoriesResult);
            }
            $where['d.CategoryID'] = $visibleFollowedCategories;
        } elseif ($categoryIDs) {
            $where['d.CategoryID'] = $announcementsWhere['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $visibleCategoriesResult = CategoryModel::instance()->getVisibleCategoryIDs(['filterHideDiscussions' => true]);
            if ($visibleCategoriesResult !== true) {
                $where['d.CategoryID'] = $visibleCategoriesResult;
            }
        }

        // Get Discussion Count
        $CountDiscussions = $DiscussionModel->getCount($where);

        $this->checkPageRange($Offset, $CountDiscussions);

        if ($MaxPages) {
            $CountDiscussions = min($MaxPages * $Limit, $CountDiscussions);
        }

        $this->setData('CountDiscussions', $CountDiscussions);

        // Get Announcements
        $this->AnnounceData = $Offset == 0 ? $DiscussionModel->getAnnouncements($announcementsWhere) : false;
        $this->setData('Announcements', $this->AnnounceData !== false ? $this->AnnounceData : [], true);

        // Get Discussions
        $this->DiscussionData = $DiscussionModel->getWhereRecent($where, $Limit, $Offset);

        $this->setData('Discussions', $this->DiscussionData, true);
        $this->setJson('Loading', $Offset.' to '.$Limit);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'discussions/{Page}');
        }
        $queryString = DiscussionModel::getSortFilterQueryString($DiscussionModel->getSort(), $DiscussionModel->getFilters());
        $this->setData('_PagerUrl', $this->data('_PagerUrl').$queryString);
        $this->Pager = $PagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Offset,
            $Limit,
            $this->data('CountDiscussions'),
            $this->data('_PagerUrl')
        );

        PagerModule::current($this->Pager);

        $this->setData('_Page', $Page);
        $this->setData('_Limit', $Limit);
        $this->fireEvent('AfterBuildPager');

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'discussions';
        }

        $this->render();
    }

    /**
     * @deprecated since 2.3
     */
    public function unread($page = '0') {
        deprecated(__METHOD__);

        if (!Gdn::session()->isValid()) {
            redirectTo('/discussions/index');
        }

        // Figure out which discussions layout to choose (Defined on "Homepage" settings page).
        $layout = c('Vanilla.Discussions.Layout');
        switch ($layout) {
            case 'table':
                if ($this->SyndicationMethod == SYNDICATION_NONE) {
                    $this->View = 'table';
                }
                break;
            default:
                // $this->View = 'index';
                break;
        }
        Gdn_Theme::section('DiscussionList');

        // Determine offset from $Page
        list($page, $limit) = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));
        $this->canonicalUrl(url(concatSep('/', 'discussions', 'unread', pageNumber($page, $limit, true, false)), true));

        // Validate $Page
        if (!is_numeric($page) || $page < 0) {
            $page = 0;
        }

        // Setup head.
        if (!$this->data('Title')) {
            $title = c('Garden.HomepageTitle');
            if ($title) {
                $this->title($title, '');
            } else {
                $this->title(t('Unread Discussions'));
            }
        }
        if (!$this->description()) {
            $this->description(c('Garden.Description', null));
        }
        if ($this->Head) {
            $this->Head->addRss(url('/discussions/unread/feed.rss', true), $this->Head->title());
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');
        $this->addModule('TagModule');

        $this->setData('Breadcrumbs', [
            ['Name' => t('Discussions'), 'Url' => '/discussions'],
            ['Name' => t('Unread'), 'Url' => '/discussions/unread']
        ]);


        // Set criteria & get discussions data
        $this->setData('Category', false, true);
        $discussionModel = new DiscussionModel();
        $discussionModel->setSort(Gdn::request()->get());
        $discussionModel->setFilters(Gdn::request()->get());
        $this->setData('Sort', $discussionModel->getSort());
        $this->setData('Filters', $discussionModel->getFilters());

        // Get Discussion Count
        $countDiscussions = $discussionModel->getUnreadCount();
        $this->setData('CountDiscussions', $countDiscussions);

        // Get Discussions
        $this->DiscussionData = $discussionModel->getUnread($page, $limit, [
            'd.CategoryID' => CategoryModel::instance()->getVisibleCategoryIDs(['filterHideDiscussions' => true])
        ]);

        $this->setData('Discussions', $this->DiscussionData, true);
        $this->setJson('Loading', $page.' to '.$limit);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $page,
            $limit,
            $countDiscussions,
            'discussions/unread/%1$s'
        );
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'discussions/unread/{Page}');
        }
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildPager');

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'discussions';
        }

        $this->render();
    }

    /**
     * Highlight route and include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        $this->ShowOptions = true;
        $this->Menu->highlightRoute('/discussions');
        $this->addJsFile('discussions.js');

        // Inform moderator of checked comments in this discussion
        $checkedDiscussions = Gdn::session()->getAttribute('CheckedDiscussions', []);
        if (count($checkedDiscussions) > 0) {
            ModerationController::informCheckedDiscussions($this);
        }

        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);

        /**
         * The default Cache-Control header does not include no-store, which can cause issues (e.g. inaccurate unread
         * status or new comment counts) when users visit the discussion list via the browser's back button.  The same
         * check is performed here as in Gdn_Controller before the Cache-Control header is added, but this value
         * includes the no-store specifier.
         */
        if (Gdn::session()->isValid()) {
            $this->setHeader('Cache-Control', 'private, no-cache, no-store, max-age=0, must-revalidate');
        }

        $this->fireEvent('AfterInitialize');
    }

    /**
     * Display discussions the user has bookmarked.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $Offset Number of discussions to skip.
     */
    public function bookmarked($page = '0') {
        $this->permission('Garden.SignIn.Allow');
        Gdn_Theme::section('DiscussionList');

        // Figure out which discussions layout to choose (Defined on "Homepage" settings page).
        $layout = c('Vanilla.Discussions.Layout');
        switch ($layout) {
            case 'table':
                if ($this->SyndicationMethod == SYNDICATION_NONE) {
                    $this->View = 'table';
                }
                break;
            default:
                $this->View = 'index';
                break;
        }

        // Determine offset from $Page
        list($page, $limit) = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));
        $this->canonicalUrl(url(concatSep('/', 'discussions', 'bookmarked', pageNumber($page, $limit, true, false)), true));

        // Validate $Page
        if (!is_numeric($page) || $page < 0) {
            $page = 0;
        }

        $discussionModel = new DiscussionModel();
        $discussionModel->setSort(Gdn::request()->get());
        $discussionModel->setFilters(Gdn::request()->get());
        $this->setData('Sort', $discussionModel->getSort());
        $this->setData('Filters', $discussionModel->getFilters());

        $wheres = [
            'w.Bookmarked' => '1',
            'w.UserID' => Gdn::session()->UserID
        ];

        $this->DiscussionData = $discussionModel->get($page, $limit, $wheres);
        $this->setData('Discussions', $this->DiscussionData);
        $countDiscussions = $discussionModel->getCount($wheres);
        $this->setData('CountDiscussions', $countDiscussions);
        $this->Category = false;

        $this->setJson('Loading', $page.' to '.$limit);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildBookmarkedPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $page,
            $limit,
            $countDiscussions,
            'discussions/bookmarked/%1$s'
        );

        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'discussions/bookmarked/{Page}');
        }
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildBookmarkedPager');

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'discussions';
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('TagModule');

        // Render default view (discussions/bookmarked.php)
        $this->setData('Title', t('My Bookmarks'));
        $this->setData('Breadcrumbs', [['Name' => t('My Bookmarks'), 'Url' => '/discussions/bookmarked']]);
        $this->render();
    }

    public function bookmarkedPopin() {
        $this->permission('Garden.SignIn.Allow');

        $discussionModel = new DiscussionModel();
        $wheres = [
            'w.Bookmarked' => '1',
            'w.UserID' => Gdn::session()->UserID
        ];

        $discussions = $discussionModel->get(0, 5, $wheres)->result();
        $this->setData('Title', t('Bookmarks'));
        $this->setData('Discussions', $discussions);
        $this->render('Popin');
    }

    /**
     * @return array
     */
    public function getCategoryIDs() {
        return $this->categoryIDs;
    }

    /**
     * @param array $categoryIDs
     */
    public function setCategoryIDs($categoryIDs) {
        $this->categoryIDs = $categoryIDs;
    }

    /**
     * Display discussions started by the user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $offset Number of discussions to skip.
     */
    public function mine($page = 'p1') {
        $this->permission('Garden.SignIn.Allow');
        Gdn_Theme::section('DiscussionList');

        // Set criteria & get discussions data
        list($offset, $limit) = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));
        $session = Gdn::session();
        $wheres = ['d.InsertUserID' => $session->UserID];

        $discussionModel = new DiscussionModel();
        $discussionModel->setSort(Gdn::request()->get());
        $discussionModel->setFilters(Gdn::request()->get());
        $this->setData('Sort', $discussionModel->getSort());
        $this->setData('Filters', $discussionModel->getFilters());

        $this->DiscussionData = $discussionModel->get($offset, $limit, $wheres);
        $this->setData('Discussions', $this->DiscussionData);
        $countDiscussions = $this->setData('CountDiscussions', $discussionModel->getCount($wheres));

        $this->View = 'index';
        if (c('Vanilla.Discussions.Layout') === 'table') {
            $this->View = 'table';
        }

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'MorePager';
        $this->fireEvent('BeforeBuildMinePager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->MoreCode = 'More Discussions';
        $this->Pager->LessCode = 'Newer Discussions';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $offset,
            $limit,
            $countDiscussions,
            'discussions/mine/%1$s'
        );

        $this->setData('_PagerUrl', 'discussions/mine/{Page}');
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);

        $this->fireEvent('AfterBuildMinePager');

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'discussions';
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');
        $this->addModule('TagModule');

        // Render view
        $this->setData('Title', t('My Discussions'));
        $this->setData('Breadcrumbs', [['Name' => t('My Discussions'), 'Url' => '/discussions/mine']]);
        $this->render();
    }

    public function userBookmarkCount($userID = false) {
        if ($userID === false) {
            $userID = Gdn::session()->UserID;
        }

        if ($userID !== Gdn::session()->UserID) {
            $this->permission('Garden.Settings.Manage');
        }

        if (!$userID) {
            $countBookmarks = null;
        } else {
            if ($userID == Gdn::session() && isset(Gdn::session()->User->CountBookmarks)) {
                $countBookmarks = Gdn::session()->User->CountBookmarks;
            } else {
                $userModel = new UserModel();
                $user = $userModel->getID($userID, DATASET_TYPE_ARRAY);
                $countBookmarks = $user['CountBookmarks'];
            }

            if ($countBookmarks === null) {
                $countBookmarks = Gdn::sql()
                    ->select('DiscussionID', 'count', 'CountBookmarks')
                    ->from('UserDiscussion')
                    ->where('Bookmarked', '1')
                    ->where('UserID', $userID)
                    ->get()->value('CountBookmarks', 0);

                Gdn::userModel()->setField($userID, 'CountBookmarks', $countBookmarks);
            }
        }
        $this->setData('CountBookmarks', $countBookmarks);
        $this->setData('_Value', $countBookmarks);
        $this->xRender('Value', 'utility', 'dashboard');
    }

    /**
     * Takes a set of discussion identifiers and returns their comment counts in the same order.
     */
    public function getCommentCounts() {
        $this->allowJSONP(true);

        $vanilla_identifier = val('vanilla_identifier', $_GET);
        if (!is_array($vanilla_identifier)) {
            $vanilla_identifier = [$vanilla_identifier];
        }

        $vanilla_identifier = array_unique($vanilla_identifier);

        $finalData = array_fill_keys($vanilla_identifier, 0);
        $misses = [];
        $cacheKey = 'embed.comments.count.%s';
        $originalIDs = [];
        foreach ($vanilla_identifier as $foreignID) {
            $hashedForeignID = foreignIDHash($foreignID);

            // Keep record of non-hashed identifiers for the reply
            $originalIDs[$hashedForeignID] = $foreignID;

            $realCacheKey = sprintf($cacheKey, $hashedForeignID);
            $comments = Gdn::cache()->get($realCacheKey);
            if ($comments !== Gdn_Cache::CACHEOP_FAILURE) {
                $finalData[$foreignID] = $comments;
            } else {
                $misses[] = $hashedForeignID;
            }
        }

        if (sizeof($misses)) {
            $countData = Gdn::sql()
                ->select('ForeignID, CountComments')
                ->from('Discussion')
                ->where('Type', 'page')
                ->whereIn('ForeignID', $misses)
                ->get()->resultArray();

            foreach ($countData as $row) {
                // Get original identifier to send back
                $foreignID = $originalIDs[$row['ForeignID']];
                $finalData[$foreignID] = $row['CountComments'];

                // Cache using the hashed identifier
                $realCacheKey = sprintf($cacheKey, $row['ForeignID']);
                Gdn::cache()->store($realCacheKey, $row['CountComments'], [
                    Gdn_Cache::FEATURE_EXPIRY => 60
                ]);
            }
        }

        $this->setData('CountData', $finalData);
        $this->DeliveryMethod = DELIVERY_METHOD_JSON;
        $this->DeliveryType = DELIVERY_TYPE_DATA;
        $this->render();
    }

    /**
     * Set user preference for sorting discussions.
     *
     * @param string $target The target to redirect to.
     */
    public function sort($target = '') {
        deprecated("sort");

        if (!Gdn::session()->isValid()) {
            throw permissionException();
        }

        if (!$this->Request->isAuthenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        if ($target) {
            redirectTo($target);
        }

        // Send sorted discussions.
        $this->setData('Deprecated', true);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->render();
    }

    /**
     * Endpoint for the PromotedContentModule's data.
     *
     * Parameters & values must be lowercase and via GET.
     *
     * @see PromotedContentModule
     */
    public function promoted() {
        // Create module & set data.
        $promotedModule = new PromotedContentModule();
        $status = $promotedModule->load(Gdn::request()->get());
        if ($status === true) {
            // Good parameters.
            $promotedModule->getData();
            $this->setData('Content', $promotedModule->data('Content'));
            $this->setData('Title', t('Promoted Content'));
            $this->setData('View', c('Vanilla.Discussions.Layout'));
            $this->setData('EmptyMessage', t('No discussions were found.'));

            // Pass display properties to the view.
            $this->Group = $promotedModule->Group;
            $this->TitleLimit = $promotedModule->TitleLimit;
            $this->BodyLimit = $promotedModule->BodyLimit;
        } else {
            $this->setData('Errors', $status);
        }

        $this->deliveryMethod();
        Gdn_Theme::section('PromotedContent');
        $this->render('promoted', 'modules', 'vanilla');
    }

    /**
     * Add the discussions/tagged/{TAG} endpoint.
     */
    public function tagged() {
        if (!c('Tagging.Discussions.Enabled')) {
            throw new Exception('Not found', 404);
        }

        Gdn_Theme::section('DiscussionList');

        $args = $this->RequestArgs;
        $get = array_change_key_case($this->Request->get());

        if ($useCategories = c('Vanilla.Tagging.UseCategories')) {
            // The url is in the form /category/tag/p1
            $categoryCode = val(0, $args);
            $tag = val(1, $args);
            $page = val(2, $args);
        } else {
            // The url is in the form /tag/p1
            $categoryCode = '';
            $tag = val(0, $args);
            $page = val(1, $args);
        }

        // Look for explcit values.
        $categoryCode = val('category', $get, $categoryCode);
        $tag = val('tag', $get, $tag);
        $page = val('page', $get, $page);
        $category = CategoryModel::categories($categoryCode);

        $tag = stringEndsWith($tag, '.rss', true, true);
        list($offset, $limit) = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));

        $multipleTags = strpos($tag, ',') !== false;

        $this->setData('Tag', $tag, true);

        $tagModel = TagModel::instance();
        $recordCount = false;
        if (!$multipleTags) {
            $tags = $tagModel->getWhere(['Name' => $tag])->resultArray();

            if (count($tags) == 0) {
                throw notFoundException('Page');
            }

            if (count($tags) > 1) {
                foreach ($tags as $tagRow) {
                    if ($tagRow['CategoryID'] == val('CategoryID', $category)) {
                        break;
                    }
                }
            } else {
                $tagRow = array_pop($tags);
            }
            $tags = $tagModel->getRelatedTags($tagRow);

            $recordCount = $tagRow['CountDiscussions'];
            $this->setData('CountDiscussions', $recordCount);
            $this->setData('Tags', $tags);
            $this->setData('Tag', $tagRow);

            $childTags = $tagModel->getChildTags($tagRow['TagID']);
            $this->setData('ChildTags', $childTags);
        }

        $this->title(htmlspecialchars($tagRow['FullName']));
        $urlTag = empty($categoryCode) ? rawurlencode($tag) : rawurlencode($categoryCode).'/'.rawurlencode($tag);
        if (urlencode($tag) == $tag) {
            $this->canonicalUrl(url(concatSep('/', "/discussions/tagged/$urlTag", pageNumber($offset, $limit, true)), true));
            $feedUrl = url(concatSep('/', "/discussions/tagged/$urlTag/feed.rss", pageNumber($offset, $limit, true, false)), '//');
        } else {
            $this->canonicalUrl(url(concatSep('/', 'discussions/tagged', pageNumber($offset, $limit, true)).'?Tag='.$urlTag, true));
            $feedUrl = url(concatSep('/', 'discussions/tagged', pageNumber($offset, $limit, true, false), 'feed.rss').'?Tag='.$urlTag, '//');
        }

        if ($this->Head) {
            $this->addJsFile('discussions.js');
            $this->Head->addRss($feedUrl, $this->Head->title());
        }

        if (!is_numeric($offset) || $offset < 0) {
            $offset = 0;
        }

        // Add Modules
        $this->addModule('NewDiscussionModule');
        $this->addModule('DiscussionFilterModule');
        $this->addModule('BookmarkedModule');

        $this->setData('Category', false, true);

        $this->AnnounceData = false;
        $this->setData('Announcements', [], true);

        $this->DiscussionData = $tagModel->getDiscussions($tag, $limit, $offset);

        $this->setData('Discussions', $this->DiscussionData, true);
        $this->setJson('Loading', $offset.' to '.$limit);

        // Build a pager.
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', "/discussions/tagged/$urlTag/{Page}");
        }
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $offset,
            $limit,
            $recordCount,
            $this->data('_PagerUrl')
        );
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildPager');

        $this->View = c('Vanilla.Discussions.Layout') == 'table' && $this->SyndicationMethod == SYNDICATION_NONE ? 'table' : 'index';
        $this->render($this->View, 'discussions', 'vanilla');
    }
}
