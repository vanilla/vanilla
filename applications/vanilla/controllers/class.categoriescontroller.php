<?php
/**
 * Categories controller
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying categories via /categoris endpoint.
 */
class CategoriesController extends VanillaController {

    /** @var array Models to include.*/
    public $Uses = ['Database', 'Form', 'CategoryModel'];

    /** @var CategoryModel */
    public $CategoryModel;

    /**  @var bool Should the discussions have their options available. */
    public $ShowOptions = true;

    /** @var int Unique identifier. */
    public $CategoryID;

    /** @var object Category object. */
    public $Category;

    /** @var bool Value indicating if the category-following filter should be displayed when rendering a view */
    public $enableFollowingFilter = false;


    /**
     * @var \Closure $categoriesCompatibilityCallback A backwards-compatible callback to get `$this->data('Categories')`.
     */
    private $categoriesCompatibilityCallback;

    /**
     *
     *
     * @param $category
     * @param $month
     * @param bool $page
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function archives($category, $month, $page = false) {
        $category = CategoryModel::categories($category);
        if (!$category) {
            throw notFoundException('Category');
        }

        if (!$category['PermsDiscussionsView']) {
            throw permissionException();
        }

        $timestamp = strtotime($month);
        if (!$timestamp) {
            throw new Gdn_UserException("The archive month is not a valid date.");
        }

        $this->setData('Category', $category);

        // Round the month to the first day.
        $from = gmdate('Y-m-01', $timestamp);
        $to = gmdate('Y-m-01', strtotime('+1 month', strtotime($from)));

        // Grab the discussions.
        list($offset, $limit) = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));
        $where = [
            'CategoryID' => $category['CategoryID'],
            'Announce' => 'all',
            'DateInserted >=' => $from,
            'DateInserted <' => $to];

        saveToConfig('Vanilla.Discussions.SortField', 'd.DateInserted', false);
        $discussionModel = new DiscussionModel();
        $discussionModel->setSort(Gdn::request()->get());
        $discussionModel->setFilters(Gdn::request()->get());
        $this->setData('Sort', $discussionModel->getSort());
        $this->setData('Filters', $discussionModel->getFilters());
        $discussions = $discussionModel->getWhereRecent($where, $limit, $offset);
        $this->DiscussionData = $this->setData('Discussions', $discussions);
        $this->setData('_CurrentRecords', count($discussions));
        $this->setData('_Limit', $limit);

        $canonical = '/categories/archives/'.rawurlencode($category['UrlCode']).'/'.gmdate('Y-m', $timestamp);
        $page = pageNumber($offset, $limit, true, false);
        $this->canonicalUrl(url($canonical.($page ? '?page='.$page : ''), true));

        PagerModule::current()->configure($offset, $limit, false, $canonical.'?page={Page}');

//      PagerModule::current()->Offset = $Offset;
//      PagerModule::current()->Url = '/categories/archives'.rawurlencode($Category['UrlCode']).'?page={Page}';

        Gdn_Theme::section(val('CssClass', $category));
        Gdn_Theme::section('DiscussionList');

        $this->title(htmlspecialchars(val('Name', $category, '')));
        $this->description(sprintf(t("Archives for %s"), gmdate('F Y', strtotime($from))), true);
        $this->addJsFile('discussions.js');
        $this->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex']);

        $this->ControllerName = 'DiscussionsController';
        $this->CssClass = 'Discussions';

        $this->render();
    }

    /**
     * Build a structured tree of children for the specified category.
     *
     * @param int|string|object|array|null $category Category or code/ID to build the tree for. Null for all.
     * @param string|null $displayAs What display should the tree be configured for?
     * @param bool $recent Join in recent record info?
     * @param bool $watching Filter categories by "watching" status?
     * @return array
     */
    private function getCategoryTree($category = null, $displayAs = null, $recent = false, $watching = false) {
        $categoryIdentifier = null;

        if (is_string($category) || is_numeric($category)) {
            $category = CategoryModel::categories($category);
        }

        if ($category) {
            if ($displayAs === null) {
                $displayAs = val('DisplayAs', $category, 'Discussions');
            }
            $categoryIdentifier = val('CategoryID', $category, null);
        }

        switch ($displayAs) {
            case 'Flat':
                $perPage = c('Vanilla.Categories.PerPage', 30);
                $page = Gdn::request()->get('Page', Gdn::request()->get('page', null));
                list($offset, $limit) = offsetLimit($page, $perPage);
                $categoryTree = $this->CategoryModel->getTreeAsFlat($categoryIdentifier, $offset, $limit);
                $this->setData('_Limit', $perPage);
                $this->setData('_CurrentRecords', count($categoryTree));
                break;
            case 'Categories':
            case 'Discussions':
            case 'Default':
            case 'Nested':
            default:
            $categoryTree = $this->CategoryModel
                    ->setJoinUserCategory(true)
                    ->getChildTree(
                        $categoryIdentifier ?: null,
                        ['depth' => CategoryModel::instance()->getMaxDisplayDepth() ?: 10]
                    );
        }

        if ($recent) {
            $this->CategoryModel->joinRecent($categoryTree);
        }

        return $categoryTree;
    }

    /**
     * Get a flattened tree representing the current user's followed categories.
     *
     * @param bool $recent Include recent post information?
     * @param array|null $filterIDs An array of category IDs. Filter result to a subset of these categories.
     * @return array
     */
    private function getFollowed($recent = false, $filterIDs = null) {
        if ($filterIDs !== null && !is_array($filterIDs)) {
            throw new InvalidArgumentException('Filter IDs must be in an array.');
        }

        $perPage = c('Vanilla.Categories.PerPage', 30);
        $page = Gdn::request()->get(
            'Page',
            Gdn::request()->get('page', null)
        );
        list($offset, $limit) = offsetLimit($page, $perPage);

        $where = ['Followed' => true];

        if (!empty($filterIDs)) {
            $where['CategoryID'] = $filterIDs;
        }

        $result = $this->CategoryModel
            ->getWhere($where, '', 'asc', $limit, $offset)
            ->resultArray();
        $result = $this->CategoryModel->flattenCategories($result);

        if ($recent) {
            $this->CategoryModel->joinRecent($result);
        }

        $this->setData('_Limit', $perPage);
        $this->setData('_CurrentRecords', count($result));

        return $result;
    }

    /**
     * "Table" layout for categories. Mimics more traditional forum category layout.
     *
     * @param string $category
     * @param string $displayAs
     */
    public function table($category = '', $displayAs = '') {
        if ($this->SyndicationMethod == SYNDICATION_NONE) {
            $this->View = $displayAs === 'Flat' ? 'flat_table' : 'table';
        } else {
            $this->View = $displayAs === 'Flat' ? 'flat_all' : 'all';
        }
        $this->all($category, $displayAs);
    }

    /**
     * Endpoint that returns a flattened list of children categories in JSON format. Collapses the categories,
     * so we only retrieve the child categories that are not nested under a nested or flat category.
     * Includes the category options that appear in the category settings dropdown in the response.
     *
     * @param int $parentID The ID of the parent to retrieve categories under.
     */
    public function getFlattenedChildren($parentID = -1) {
        $options = ['maxdepth' => 10, 'collapsecategories' => true];
        $categories = $this->CategoryModel->getChildTree($parentID, $options);
        $categories = $this->CategoryModel->flattenTree($categories);

        foreach ($categories as &$category) {
            $category['Options'] = $this->getOptions($category);
        }

        $this->setData('Categories', $categories);
        $this->deliveryType(DELIVERY_TYPE_DATA);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Returns an array representation of the dropdown object, ready to add to a data array.
     *
     * @param array|object $category The category to retrieve the dropdown options for.
     * @return array
     */
    private function getOptions($category) {
        $cdd = CategoryModel::getCategoryDropdown($category);
        return $cdd->toArray();
    }


    /**
     * Show all discussions in a particular category.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $categoryIdentifier Unique category slug or ID.
     * @param int $offset Number of discussions to skip.
     */
    public function index($categoryIdentifier = '', $page = '0') {
        // Figure out which category layout to choose (Defined on "Homepage" settings page).
        $layout = c('Vanilla.Categories.Layout');

        if ($this->CategoryModel->followingEnabled()) {
            // Only use the following filter on the root category level.
            $this->enableFollowingFilter = $categoryIdentifier === '';
            $this->fireEvent('EnableFollowingFilter', [
                'CategoryIdentifier' => $categoryIdentifier,
                'EnableFollowingFilter' => &$this->enableFollowingFilter
            ]);

            $followed = paramPreference(
                'followed',
                'FollowedCategories',
                'Vanilla.SaveFollowingPreference',
                null,
                Gdn::request()->get('save')
            );
        } else {
            $this->enableFollowingFilter = $followed = false;
        }
        $this->setData('EnableFollowingFilter', $this->enableFollowingFilter);
        $this->setData('Followed', $followed);

        if ($categoryIdentifier == '') {
            switch ($layout) {
                case 'mixed':
                    $this->View = 'discussions';
                    $this->discussions();
                    break;
                case 'table':
                    $this->table();
                    break;
                default:
                    $this->View = 'all';
                    $this->all('', CategoryModel::getRootDisplayAs());
                    break;
            }
            return;
        } else {
            $category = CategoryModel::categories($categoryIdentifier);

            if (empty($category)) {
                throw notFoundException();
            }
            $category = (object)$category;

            // Check permission
            $this->permission('Vanilla.Discussions.View', true, 'Category', val('PermissionCategoryID', $category));

            Gdn_Theme::section($category->CssClass);

            // Load the breadcrumbs.
            $this->setData('Breadcrumbs', CategoryModel::getAncestors(val('CategoryID', $category)));

            $this->setData('Category', $category, true);

            $this->title(htmlspecialchars(val('Name', $category, '')));
            $this->description(val('Description', $category), true);

            switch ($category->DisplayAs) {
                case 'Flat':
                case 'Heading':
                case 'Categories':
                    $stopHeadings = val('Depth', $category) > CategoryModel::instance()->getNavDepth();
                    CategoryModel::instance()->setStopHeadingsCalculation($stopHeadings);
                    if ($this->SyndicationMethod != SYNDICATION_NONE) {
                        // RSS can't show a category list so just tell it to expand all categories.
                        saveToConfig('Vanilla.ExpandCategories', true, false);
                    } else {
                        // This category is an overview style category and displays as a category list.
                        switch ($layout) {
                            case 'mixed':
                                $this->View = 'discussions';
                                $this->discussions($categoryIdentifier);
                                break;
                            case 'table':
                                $this->table($categoryIdentifier, $category->DisplayAs);
                                break;
                            default:
                                $this->View = 'all';
                                $this->all($categoryIdentifier, $category->DisplayAs);
                                break;
                        }
                        return;
                    }
                    break;
            }

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
                    // $this->View = 'index';
                    break;
            }

            $this->setData('CategoryTree', $this->getCategoryTree(
                $categoryIdentifier, val('DisplayAs', $category)
            ));

            // Add a backwards-compatibility shim for the old categories.
            $this->categoriesCompatibilityCallback = function () use ($categoryIdentifier) {
                $categories = CategoryModel::getSubtree($categoryIdentifier, false);
                return $categories;
            };

            // Setup head
            $this->Menu->highlightRoute('/discussions');
            if ($this->Head) {
                $this->addJsFile('discussions.js');
                $this->Head->addRss(categoryUrl($category) . '/feed.rss', $this->Head->title());
            }

            // Set CategoryID
            $categoryID = val('CategoryID', $category);
            $this->setData('CategoryID', $categoryID, true);

            // Add modules
            $this->addModule('NewDiscussionModule');
            $this->addModule('DiscussionFilterModule');
            $this->addModule('CategoriesModule');
            $this->addModule('BookmarkedModule');
            $this->addModule('TagModule');

            // Get a DiscussionModel
            $discussionModel = new DiscussionModel();
            $discussionModel->setSort(Gdn::request()->get());
            $discussionModel->setFilters(Gdn::request()->get());
            $this->setData('Sort', $discussionModel->getSort());
            $this->setData('Filters', $discussionModel->getFilters());

            $categoryIDs = [$categoryID];
            if (c('Vanilla.ExpandCategories')) {
                $categoryIDs = array_merge($categoryIDs, array_column($this->data('Categories'), 'CategoryID'));
            }
            $wheres = ['d.CategoryID' => $categoryIDs];
            $this->setData('_ShowCategoryLink', count($categoryIDs) > 1);

            // Check permission.
            $this->categoryPermission($category, 'Vanilla.Discussions.View');

            // Set discussion meta data.
            $this->EventArguments['PerPage'] = c('Vanilla.Discussions.PerPage', 30);
            $this->fireEvent('BeforeGetDiscussions');
            list($offset, $limit) = offsetLimit($page, $this->EventArguments['PerPage']);
            if (!is_numeric($offset) || $offset < 0) {
                $offset = 0;
            }

            $page = pageNumber($offset, $limit);

            // Allow page manipulation
            $this->EventArguments['Page'] = &$page;
            $this->EventArguments['Offset'] = &$offset;
            $this->EventArguments['Limit'] = &$limit;
            $this->fireEvent('AfterPageCalculation');

            // We want to limit the number of pages on large databases because requesting a super-high page can kill the db.
            $maxPages = c('Vanilla.Categories.MaxPages');
            if ($maxPages && $page > $maxPages) {
                throw notFoundException();
            }

            $countDiscussions = $discussionModel->getCount($wheres);
            $this->checkPageRange($offset, $countDiscussions);

            if ($maxPages && $maxPages * $limit < $countDiscussions) {
                $countDiscussions = $maxPages * $limit;
            }

            $this->setData('CountDiscussions', $countDiscussions);
            $this->setData('_Limit', $limit);

            // We don't wan't child categories in announcements.
            $wheres['d.CategoryID'] = $categoryID;
            $announceData = $discussionModel->getAnnouncements($wheres, $offset, $limit);
            $this->AnnounceData = $this->setData('Announcements', $announceData);
            $wheres['d.CategoryID'] = $categoryIDs;

            // RSS should include announcements.
            if ($this->SyndicationMethod !== SYNDICATION_NONE) {
                $wheres['Announce'] = 'all';
            }

            $this->DiscussionData = $this->setData('Discussions', $discussionModel->getWhereRecent($wheres, $limit, $offset));

            // Build a pager
            $pagerFactory = new Gdn_PagerFactory();
            $url = categoryUrl($categoryIdentifier);

            $this->EventArguments['PagerType'] = 'Pager';
            $this->fireEvent('BeforeBuildPager');
            if (!$this->data('_PagerUrl')) {
                $this->setData('_PagerUrl', $url.'/{Page}');
            }
            $queryString = DiscussionModel::getSortFilterQueryString($discussionModel->getSort(), $discussionModel->getFilters());
            $this->setData('_PagerUrl', $this->data('_PagerUrl').$queryString);

            $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
            $this->Pager->ClientID = 'Pager';
            $this->Pager->configure(
                $offset,
                $limit,
                $countDiscussions,
                $this->data('_PagerUrl')
            );

            $this->Pager->Record = $category;
            PagerModule::current($this->Pager);
            $this->setData('_Page', $page);
            $this->setData('_Limit', $limit);
            $this->fireEvent('AfterBuildPager');

            // Set the canonical Url.
            $this->canonicalUrl(categoryUrl($category, pageNumber($offset, $limit)));

            // Change the controller name so that it knows to grab the discussion views
            $this->ControllerName = 'DiscussionsController';
            // Pick up the discussions class
            $this->CssClass = 'Discussions Category-'.val('UrlCode', $category);

            // Deliver JSON data if necessary
            if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
                $this->setJson('LessRow', $this->Pager->toString('less'));
                $this->setJson('MoreRow', $this->Pager->toString('more'));
                $this->View = 'discussions';
            }
            // Render default view.
            $this->fireEvent('BeforeCategoriesRender');
            $this->render();
        }
    }

    /**
     * Show all (nested) categories.
     *
     * @param string $Category The url code of the parent category.
     * @param string $displayAs
     * @since 2.0.17
     * @access public
     */
    public function all($Category = '', $displayAs = '') {
        // Setup head.
        $this->Menu->highlightRoute('/discussions');
        if (!$this->title()) {
            $Title = c('Garden.HomepageTitle');
            if ($Title) {
                $this->title($Title, '');
            } else {
                $this->title(t('All Categories'));
            }
        }
        Gdn_Theme::section('CategoryList');

        if (!$Category) {
            $this->description(c('Garden.Description', null));
        }

        $this->setData('Breadcrumbs', CategoryModel::getAncestors(val('CategoryID', $this->data('Category'))));

        // Set the category follow toggle before we load category data so that it affects the category query appropriately.
        $CategoryFollowToggleModule = new CategoryFollowToggleModule($this);
        $CategoryFollowToggleModule->setToggle();

        // Get category data
        $this->CategoryModel->Watching = !Gdn::session()->getPreference('ShowAllCategories');

        if ($Category) {
            $this->setData('Category', CategoryModel::categories($Category));

            $this->categoriesCompatibilityCallback = function () use ($Category) {
                $Subtree = CategoryModel::getSubtree($Category, false);
                $CategoryIDs = array_column($Subtree, 'CategoryID');
                return $this->CategoryModel->getFull($CategoryIDs)->resultArray();
            };
        } else {
            $this->categoriesCompatibilityCallback = function () {
                return $this->CategoryModel->getFull()->resultArray();
            };
        }

        if ($this->data('Followed')) {
            if ($Category) {
                $ancestor = CategoryModel::categories($Category);
                if (empty($ancestor)) {
                    throw new Gdn_UserException("Invalid category ID: {$Category}");
                }
                $tree = $this->CategoryModel->getTree($ancestor['CategoryID']);
                $flatTree = CategoryModel::flattenTree($tree);
                $filterIDs = array_column($flatTree, 'CategoryID');
            } else {
                $filterIDs = null;
            }
            $categoryTree = $this->getFollowed(true, $filterIDs);
        } else {
            $categoryTree = $this->getCategoryTree(
                $Category ?: -1,
                $Category ? null : CategoryModel::getRootDisplayAs(),
                true,
                true
            );
        }

        $this->setData('CategoryTree', $categoryTree);

        // Add modules
        $this->addModule('NewDiscussionModule');
        $this->addModule('DiscussionFilterModule');
        $this->addModule('BookmarkedModule');
        $this->addModule($CategoryFollowToggleModule);
        $this->addModule('TagModule');

        $this->canonicalUrl(url('/categories', true));

        if ($this->View === 'all' && $displayAs === 'Flat') {
            $this->View = 'flat_all';
        }

        $Location = $this->fetchViewLocation('helper_functions', 'categories', false, false);
        if ($Location) {
            include_once $Location;
        }
        $this->render();
    }

    /**
     * Show all categories and few discussions from each.
     *
     * @param string $Category The url code of the parent category.
     * @since 2.0.0
     * @access public
     */
    public function discussions($Category = '') {
        // Setup head
        $this->addJsFile('discussions.js');
        $this->Menu->highlightRoute('/discussions');

        if (!$this->title()) {
            $Title = c('Garden.HomepageTitle');
            if ($Title) {
                $this->title($Title, '');
            } else {
                $this->title(t('All Categories'));
            }
        }

        if (!$Category) {
            $this->description(c('Garden.Description', null));
        }

        Gdn_Theme::section('CategoryDiscussionList');

        // Set the category follow toggle before we load category data so that it affects the category query appropriately.
        $CategoryFollowToggleModule = new CategoryFollowToggleModule($this);
        $CategoryFollowToggleModule->setToggle();

        //$this->CategoryModel->Watching = !Gdn::session()->getPreference('ShowAllCategories');

        if ($Category) {
            $Subtree = CategoryModel::getSubtree($Category, false);
            $CategoryIDs = array_column($Subtree, 'CategoryID');
            $Categories = $this->CategoryModel->getFull($CategoryIDs)->resultArray();
        } elseif ($this->data('Followed')) {
            $Categories = $this->CategoryModel->getWhere(['Followed' => true])->resultArray();
            $Categories = array_column($Categories, null, 'CategoryID');
            $Categories = $this->CategoryModel->flattenCategories($Categories);
        } else {
            $Categories = $this->CategoryModel->getFull()->resultArray();
        }

        $this->setData('Categories', $Categories);

        // Get category data and discussions
        $this->DiscussionsPerCategory = c('Vanilla.Discussions.PerCategory', 5);
        $DiscussionModel = new DiscussionModel();
        $DiscussionModel->setSort(Gdn::request()->get());
        $DiscussionModel->setFilters(Gdn::request()->get());
        $this->setData('Sort', $DiscussionModel->getSort());
        $this->setData('Filters', $DiscussionModel->getFilters());

        $this->CategoryDiscussionData = [];
        $Discussions = [];

        foreach ($this->CategoryData->result() as $Category) {
            if ($Category->CategoryID > 0) {
                $this->CategoryDiscussionData[$Category->CategoryID] = $DiscussionModel->get(0, $this->DiscussionsPerCategory, ['d.CategoryID' => $Category->CategoryID, 'Announce' => 'all']);

                $Discussions = array_merge(
                    $Discussions,
                    $this->CategoryDiscussionData[$Category->CategoryID]->resultObject()
                );
            }
        }
        $this->setData('Discussions', $Discussions);

        // Add modules
        $this->addModule('NewDiscussionModule');
        $this->addModule('DiscussionFilterModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');
        $this->addModule($CategoryFollowToggleModule);

        // Set view and render
        $this->View = 'discussions';

        $this->canonicalUrl(url('/categories', true));
        $Path = $this->fetchViewLocation('helper_functions', 'discussions', false, false);
        if ($Path) {
            include_once $Path;
        }

        // For GetOptions function
        $Path2 = $this->fetchViewLocation('helper_functions', 'categories', false, false);
        if ($Path2) {
            include_once $Path2;
        }
        $this->render();
    }

    public function __get($name) {
        switch ($name) {
            case 'CategoryData':
//            deprecated('CategoriesController->CategoryData', "CategoriesController->data('Categories')");
                $this->CategoryData = new Gdn_DataSet($this->data('Categories'), DATASET_TYPE_ARRAY);
                $this->CategoryData->datasetType(DATASET_TYPE_OBJECT);
                return $this->CategoryData;
        }
    }

    /**
     * Highlight route.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        if (!c('Vanilla.Categories.Use')) {
            redirectTo('/discussions');
        }
        if ($this->Menu) {
            $this->Menu->highlightRoute('/categories');
        }

        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);

        /**
         * The default Cache-Control header does not include no-store, which can cause issues with outdated category
         * information (e.g. counts).  The same check is performed here as in Gdn_Controller before the Cache-Control
         * header is added, but this value includes the no-store specifier.
         */
        if (Gdn::session()->isValid()) {
            $this->setHeader('Cache-Control', 'private, no-cache, no-store, max-age=0, must-revalidate');
        }
    }

    public function tree($category = '') {
        $tree = CategoryModel::instance()->getChildTree($category);
        $this->setData('Categories', $tree);
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Returns the full list of categories for the APIv1.
     */
    public function apiV1List() {
        $categories = CategoryModel::categories();

        // Purge the root category, if present.
        if (val(-1, $categories)) {
            unset($categories[-1]);
        }

        $this->setData('Categories', $categories);
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * {@inheritdoc}
     */
    public function data($path, $default = '') {
        if (isset($this->Data[$path])) {
            return $this->Data[$path];
        }

        switch ($path) {
            case 'Categories':
                if ($this->categoriesCompatibilityCallback instanceof \Closure) {
                    deprecated('Categories', 'CategoryTree');
                    $this->Data['Categories'] = $categories = call_user_func($this->categoriesCompatibilityCallback);
                    return $categories;
                }
                return $default;
            default:
                return parent::data($path, $default);
        }
    }
}
