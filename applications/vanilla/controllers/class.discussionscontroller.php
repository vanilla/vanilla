<?php
/**
 * Discussions controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    public $Uses = array('Database', 'DiscussionModel', 'Form');

    /** @var boolean Value indicating if discussion options should be displayed when rendering the discussion view.*/
    public $ShowOptions;

    /** @var object Category object. Used to limit which discussions are returned to a particular category. */
    public $Category;

    /** @var int Unique identifier for category. */
    public $CategoryID;

    /** @var array Limit the discussions to just this list of categories, checked for view permission. */
    protected $categoryIDs;

    /**
     * "Table" layout for discussions. Mimics more traditional forum discussion layout.
     *
     * @param int $Page Multiplied by PerPage option to determine offset.
     */
    public function table($Page = '0') {
        if ($this->SyndicationMethod == SYNDICATION_NONE) {
            $this->View = 'table';
        }
        $this->Index($Page);
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

        // Check for the feed keyword.
        if ($Page === 'feed' && $this->SyndicationMethod != SYNDICATION_NONE) {
            $Page = 'p1';
        }

        // Determine offset from $Page
        list($Offset, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30), true);
        $Page = PageNumber($Offset, $Limit);

        // Allow page manipulation
        $this->EventArguments['Page'] = &$Page;
        $this->EventArguments['Offset'] = &$Offset;
        $this->EventArguments['Limit'] = &$Limit;
        $this->fireEvent('AfterPageCalculation');

        // Set canonical URL
        $this->canonicalUrl(url(ConcatSep('/', 'discussions', PageNumber($Offset, $Limit, true, false)), true));

        // We want to limit the number of pages on large databases because requesting a super-high page can kill the db.
        $MaxPages = c('Vanilla.Discussions.MaxPages');
        if ($MaxPages && $Page > $MaxPages) {
            throw notFoundException();
        }

        // Setup head.
        if (!$this->data('Title')) {
            $Title = c('Garden.HomepageTitle');
            $DefaultControllerRoute = val('Destination', Gdn::router()->GetRoute('DefaultController'));
            if ($Title && ($DefaultControllerRoute == 'discussions')) {
                $this->title($Title, '');
            } else {
                $this->title(t('Recent Discussions'));
            }
        }
        if (!$this->Description()) {
            $this->Description(c('Garden.Description', null));
        }
        if ($this->Head) {
            $this->Head->AddRss(url('/discussions/feed.rss', true), $this->Head->title());
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');
        $this->setData('Breadcrumbs', array(array('Name' => t('Recent Discussions'), 'Url' => '/discussions')));


        // Set criteria & get discussions data
        $this->setData('Category', false, true);
        $DiscussionModel = new DiscussionModel();

        // Check for individual categories.
        $categoryIDs = $this->getCategoryIDs();
        $where = array();
        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $DiscussionModel->Watching = true;
        }

        // Get Discussion Count
        $CountDiscussions = $DiscussionModel->getCount($where);

        if ($MaxPages) {
            $CountDiscussions = min($MaxPages * $Limit, $CountDiscussions);
        }

        $this->setData('CountDiscussions', $CountDiscussions);

        // Get Announcements
        $this->AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements($where) : false;
        $this->setData('Announcements', $this->AnnounceData !== false ? $this->AnnounceData : array(), true);

        // Get Discussions
        $this->DiscussionData = $DiscussionModel->getWhere($where, $Offset, $Limit);

        $this->setData('Discussions', $this->DiscussionData, true);
        $this->setJson('Loading', $Offset.' to '.$Limit);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'discussions/{Page}');
        }
        $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Offset,
            $Limit,
            $this->data('CountDiscussions'),
            $this->data('_PagerUrl')
        );

        PagerModule::Current($this->Pager);

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

    public function unread($Page = '0') {
        if (!Gdn::session()->isValid()) {
            redirect('/discussions/index', 302);
        }

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

        // Determine offset from $Page
        list($Page, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30));
        $this->canonicalUrl(url(ConcatSep('/', 'discussions', 'unread', PageNumber($Page, $Limit, true, false)), true));

        // Validate $Page
        if (!is_numeric($Page) || $Page < 0) {
            $Page = 0;
        }

        // Setup head.
        if (!$this->data('Title')) {
            $Title = c('Garden.HomepageTitle');
            if ($Title) {
                $this->title($Title, '');
            } else {
                $this->title(t('Unread Discussions'));
            }
        }
        if (!$this->Description()) {
            $this->Description(c('Garden.Description', null));
        }
        if ($this->Head) {
            $this->Head->AddRss(url('/discussions/unread/feed.rss', true), $this->Head->title());
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');
        $this->setData('Breadcrumbs', array(
            array('Name' => t('Discussions'), 'Url' => '/discussions'),
            array('Name' => t('Unread'), 'Url' => '/discussions/unread')
        ));


        // Set criteria & get discussions data
        $this->setData('Category', false, true);
        $DiscussionModel = new DiscussionModel();
        $DiscussionModel->Watching = true;

        // Get Discussion Count
        $CountDiscussions = $DiscussionModel->GetUnreadCount();
        $this->setData('CountDiscussions', $CountDiscussions);

        // Get Discussions
        $this->DiscussionData = $DiscussionModel->GetUnread($Page, $Limit);

        $this->setData('Discussions', $this->DiscussionData, true);
        $this->setJson('Loading', $Page.' to '.$Limit);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Page,
            $Limit,
            $CountDiscussions,
            'discussions/unread/%1$s'
        );
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'discussions/unread/{Page}');
        }
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
        $CheckedDiscussions = Gdn::session()->getAttribute('CheckedDiscussions', array());
        if (count($CheckedDiscussions) > 0) {
            ModerationController::InformCheckedDiscussions($this);
        }

        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);

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
    public function bookmarked($Page = '0') {
        $this->permission('Garden.SignIn.Allow');
        Gdn_Theme::section('DiscussionList');

        // Figure out which discussions layout to choose (Defined on "Homepage" settings page).
        $Layout = c('Vanilla.Discussions.Layout');
        switch ($Layout) {
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
        list($Page, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30));
        $this->canonicalUrl(url(ConcatSep('/', 'discussions', 'bookmarked', PageNumber($Page, $Limit, true, false)), true));

        // Validate $Page
        if (!is_numeric($Page) || $Page < 0) {
            $Page = 0;
        }

        $DiscussionModel = new DiscussionModel();
        $Wheres = array(
            'w.Bookmarked' => '1',
            'w.UserID' => Gdn::session()->UserID
        );

        $this->DiscussionData = $DiscussionModel->get($Page, $Limit, $Wheres);
        $this->setData('Discussions', $this->DiscussionData);
        $CountDiscussions = $DiscussionModel->getCount($Wheres);
        $this->setData('CountDiscussions', $CountDiscussions);
        $this->Category = false;

        $this->setJson('Loading', $Page.' to '.$Limit);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildBookmarkedPager');
        $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Page,
            $Limit,
            $CountDiscussions,
            'discussions/bookmarked/%1$s'
        );

        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'discussions/bookmarked/{Page}');
        }
        $this->setData('_Page', $Page);
        $this->setData('_Limit', $Limit);
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

        // Render default view (discussions/bookmarked.php)
        $this->setData('Title', t('My Bookmarks'));
        $this->setData('Breadcrumbs', array(array('Name' => t('My Bookmarks'), 'Url' => '/discussions/bookmarked')));
        $this->render();
    }

    public function bookmarkedPopin() {
        $this->permission('Garden.SignIn.Allow');

        $DiscussionModel = new DiscussionModel();
        $Wheres = array(
            'w.Bookmarked' => '1',
            'w.UserID' => Gdn::session()->UserID
        );

        $Discussions = $DiscussionModel->get(0, 5, $Wheres)->result();
        $this->setData('Title', t('Bookmarks'));
        $this->setData('Discussions', $Discussions);
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
     * @param int $Offset Number of discussions to skip.
     */
    public function mine($Page = 'p1') {
        $this->permission('Garden.SignIn.Allow');
        Gdn_Theme::section('DiscussionList');

        // Set criteria & get discussions data
        list($Offset, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30));
        $Session = Gdn::session();
        $Wheres = array('d.InsertUserID' => $Session->UserID);
        $DiscussionModel = new DiscussionModel();
        $this->DiscussionData = $DiscussionModel->get($Offset, $Limit, $Wheres);
        $this->setData('Discussions', $this->DiscussionData);
        $CountDiscussions = $this->setData('CountDiscussions', $DiscussionModel->getCount($Wheres));

        $this->View = 'index';
        if (c('Vanilla.Discussions.Layout') === 'table') {
            $this->View = 'table';
        }

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'MorePager';
        $this->fireEvent('BeforeBuildMinePager');
        $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
        $this->Pager->MoreCode = 'More Discussions';
        $this->Pager->LessCode = 'Newer Discussions';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Offset,
            $Limit,
            $CountDiscussions,
            'discussions/mine/%1$s'
        );

        $this->setData('_PagerUrl', 'discussions/mine/{Page}');
        $this->setData('_Page', $Page);
        $this->setData('_Limit', $Limit);

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

        // Render view
        $this->setData('Title', t('My Discussions'));
        $this->setData('Breadcrumbs', array(array('Name' => t('My Discussions'), 'Url' => '/discussions/mine')));
        $this->render();
    }

    public function userBookmarkCount($UserID = false) {
        if ($UserID === false) {
            $UserID = Gdn::session()->UserID;
        }

        if (!$UserID) {
            $CountBookmarks = null;
        } else {
            if ($UserID == Gdn::session() && isset(Gdn::session()->User->CountBookmarks)) {
                $CountBookmarks = Gdn::session()->User->CountBookmarks;
            } else {
                $UserModel = new UserModel();
                $User = $UserModel->getID($UserID, DATASET_TYPE_ARRAY);
                $CountBookmarks = $User['CountBookmarks'];
            }

            if ($CountBookmarks === null) {
                $CountBookmarks = Gdn::sql()
                    ->select('DiscussionID', 'count', 'CountBookmarks')
                    ->from('UserDiscussion')
                    ->where('Bookmarked', '1')
                    ->where('UserID', $UserID)
                    ->get()->value('CountBookmarks', 0);

                Gdn::userModel()->setField($UserID, 'CountBookmarks', $CountBookmarks);
            }
        }
        $this->setData('CountBookmarks', $CountBookmarks);
        $this->setData('_Value', $CountBookmarks);
        $this->xRender('Value', 'utility', 'dashboard');
    }

    /**
     * Takes a set of discussion identifiers and returns their comment counts in the same order.
     */
    public function getCommentCounts() {
        $this->AllowJSONP(true);

        $vanilla_identifier = val('vanilla_identifier', $_GET);
        if (!is_array($vanilla_identifier)) {
            $vanilla_identifier = array($vanilla_identifier);
        }

        $vanilla_identifier = array_unique($vanilla_identifier);

        $FinalData = array_fill_keys($vanilla_identifier, 0);
        $Misses = array();
        $CacheKey = 'embed.comments.count.%s';
        $OriginalIDs = array();
        foreach ($vanilla_identifier as $ForeignID) {
            $HashedForeignID = ForeignIDHash($ForeignID);

            // Keep record of non-hashed identifiers for the reply
            $OriginalIDs[$HashedForeignID] = $ForeignID;

            $RealCacheKey = sprintf($CacheKey, $HashedForeignID);
            $Comments = Gdn::cache()->get($RealCacheKey);
            if ($Comments !== Gdn_Cache::CACHEOP_FAILURE) {
                $FinalData[$ForeignID] = $Comments;
            } else {
                $Misses[] = $HashedForeignID;
            }
        }

        if (sizeof($Misses)) {
            $CountData = Gdn::sql()
                ->select('ForeignID, CountComments')
                ->from('Discussion')
                ->where('Type', 'page')
                ->whereIn('ForeignID', $Misses)
                ->get()->resultArray();

            foreach ($CountData as $Row) {
                // Get original identifier to send back
                $ForeignID = $OriginalIDs[$Row['ForeignID']];
                $FinalData[$ForeignID] = $Row['CountComments'];

                // Cache using the hashed identifier
                $RealCacheKey = sprintf($CacheKey, $Row['ForeignID']);
                Gdn::cache()->store($RealCacheKey, $Row['CountComments'], array(
                    Gdn_Cache::FEATURE_EXPIRY => 60
                ));
            }
        }

        $this->setData('CountData', $FinalData);
        $this->DeliveryMethod = DELIVERY_METHOD_JSON;
        $this->DeliveryType = DELIVERY_TYPE_DATA;
        $this->render();
    }

    /**
     * Set user preference for sorting discussions.
     */
    public function sort($Target = '') {
        if (!Gdn::session()->isValid()) {
            throw permissionException();
        }

        if (!$this->Request->isAuthenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        // Get param
        $SortField = Gdn::request()->Post('DiscussionSort');
        $SortField = 'd.'.stringBeginsWith($SortField, 'd.', true, true);

        // Use whitelist here too to keep database clean
        if (!in_array($SortField, DiscussionModel::AllowedSortFields())) {
            throw new Gdn_UserException("Unknown sort $SortField.");
        }

        // Set user pref
        Gdn::userModel()->SavePreference(Gdn::session()->UserID, 'Discussions.SortField', $SortField);

        if ($Target) {
            redirect($Target);
        }

        // Send sorted discussions.
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
        $PromotedModule = new PromotedContentModule();
        $Status = $PromotedModule->Load(Gdn::request()->get());
        if ($Status === true) {
            // Good parameters.
            $PromotedModule->GetData();
            $this->setData('Content', $PromotedModule->data('Content'));
            $this->setData('Title', t('Promoted Content'));
            $this->setData('View', c('Vanilla.Discussions.Layout'));
            $this->setData('EmptyMessage', t('No discussions were found.'));

            // Pass display properties to the view.
            $this->Group = $PromotedModule->Group;
            $this->TitleLimit = $PromotedModule->TitleLimit;
            $this->BodyLimit = $PromotedModule->BodyLimit;
        } else {
            $this->setData('Errors', $Status);
        }

        $this->deliveryMethod();
        Gdn_Theme::section('PromotedContent');
        $this->render('promoted', 'modules', 'vanilla');
    }
}
