<?php
/**
 * Categories controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying categories via /categoris endpoint.
 */
class CategoriesController extends VanillaController {

    /** @var array Models to include.*/
    public $Uses = array('Database', 'Form', 'CategoryModel');

    /** @var CategoryModel */
    public $CategoryModel;

    /**  @var bool Should the discussions have their options available. */
    public $ShowOptions = true;

    /** @var int Unique identifier. */
    public $CategoryID;

    /** @var object Category object. */
    public $Category;

    /**
     *
     *
     * @param $Category
     * @param $Month
     * @param bool $Page
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function archives($Category, $Month, $Page = false) {
        $Category = CategoryModel::categories($Category);
        if (!$Category) {
            throw notFoundException('Category');
        }

        if (!$Category['PermsDiscussionsView']) {
            throw permissionException();
        }

        $Timestamp = strtotime($Month);
        if (!$Timestamp) {
            throw new Gdn_UserException("The archive month is not a valid date.");
        }

        $this->setData('Category', $Category);

        // Round the month to the first day.
        $From = gmdate('Y-m-01', $Timestamp);
        $To = gmdate('Y-m-01', strtotime('+1 month', strtotime($From)));

        // Grab the discussions.
        list($Offset, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30));
        $Where = array(
            'CategoryID' => $Category['CategoryID'],
            'Announce' => 'all',
            'DateInserted >=' => $From,
            'DateInserted <' => $To);

        saveToConfig('Vanilla.Discussions.SortField', 'd.DateInserted', false);
        $DiscussionModel = new DiscussionModel();
        $Discussions = $DiscussionModel->getWhere($Where, $Offset, $Limit);
        $this->DiscussionData = $this->setData('Discussions', $Discussions);
        $this->setData('_CurrentRecords', count($Discussions));
        $this->setData('_Limit', $Limit);

        $Canonical = '/categories/archives/'.rawurlencode($Category['UrlCode']).'/'.gmdate('Y-m', $Timestamp);
        $Page = PageNumber($Offset, $Limit, true, false);
        $this->canonicalUrl(url($Canonical.($Page ? '?page='.$Page : ''), true));

        PagerModule::Current()->configure($Offset, $Limit, false, $Canonical.'?page={Page}');

//      PagerModule::Current()->Offset = $Offset;
//      PagerModule::Current()->Url = '/categories/archives'.rawurlencode($Category['UrlCode']).'?page={Page}';

        Gdn_Theme::section(val('CssClass', $Category));
        Gdn_Theme::section('DiscussionList');

        $this->title(htmlspecialchars(val('Name', $Category, '')));
        $this->Description(sprintf(t("Archives for %s"), gmdate('F Y', strtotime($From))), true);
        $this->addJsFile('discussions.js');
        $this->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex'));

        $this->ControllerName = 'DiscussionsController';
        $this->CssClass = 'Discussions';

        $this->render();
    }

    /**
     * "Table" layout for categories. Mimics more traditional forum category layout.
     */
    public function table($Category = '') {
        if ($this->SyndicationMethod == SYNDICATION_NONE) {
            $this->View = 'table';
        } else {
            $this->View = 'all';
        }
        $this->All($Category);
    }

    /**
     * Show all discussions in a particular category.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $CategoryIdentifier Unique category slug or ID.
     * @param int $Offset Number of discussions to skip.
     */
    public function index($CategoryIdentifier = '', $Page = '0') {
        // Figure out which category layout to choose (Defined on "Homepage" settings page).
        $Layout = c('Vanilla.Categories.Layout');

        if ($CategoryIdentifier == '') {
            switch ($Layout) {
                case 'mixed':
                    $this->View = 'discussions';
                    $this->Discussions();
                    break;
                case 'table':
                    $this->table();
                    break;
                default:
                    $this->View = 'all';
                    $this->All();
                    break;
            }
            return;
        } else {
            $Category = CategoryModel::categories($CategoryIdentifier);

            if (empty($Category)) {
                // Try lowercasing before outright failing
                $LowerCategoryIdentifier = strtolower($CategoryIdentifier);
                if ($LowerCategoryIdentifier != $CategoryIdentifier) {
                    $Category = CategoryModel::categories($LowerCategoryIdentifier);
                    if ($Category) {
                        redirect("/categories/{$LowerCategoryIdentifier}", 301);
                    }
                }
                throw notFoundException();
            }
            $Category = (object)$Category;
            Gdn_Theme::section($Category->CssClass);

            // Load the breadcrumbs.
            $this->setData('Breadcrumbs', CategoryModel::GetAncestors(val('CategoryID', $Category)));

            $this->setData('Category', $Category, true);

            $this->title(htmlspecialchars(val('Name', $Category, '')));
            $this->Description(val('Description', $Category), true);


            if ($Category->DisplayAs == 'Categories') {
                if (val('Depth', $Category) > c('Vanilla.Categories.NavDepth', 0)) {
                    // Headings don't make sense if we've cascaded down one level.
                    saveToConfig('Vanilla.Categories.DoHeadings', false, false);
                }

                trace($this->deliveryMethod(), 'delivery method');
                trace($this->deliveryType(), 'delivery type');
                trace($this->SyndicationMethod, 'syndication');

                if ($this->SyndicationMethod != SYNDICATION_NONE) {
                    // RSS can't show a category list so just tell it to expand all categories.
                    saveToConfig('Vanilla.ExpandCategories', true, false);
                } else {
                    // This category is an overview style category and displays as a category list.
                    switch ($Layout) {
                        case 'mixed':
                            $this->View = 'discussions';
                            $this->Discussions($CategoryIdentifier);
                            break;
                        case 'table':
                            $this->table($CategoryIdentifier);
                            break;
                        default:
                            $this->View = 'all';
                            $this->All($CategoryIdentifier);
                            break;
                    }
                    return;
                }
            }

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
                    // $this->View = 'index';
                    break;
            }

            // Load the subtree.
            $Categories = CategoryModel::GetSubtree($CategoryIdentifier, false);
            $this->setData('Categories', $Categories);

            // Setup head
            $this->Menu->highlightRoute('/discussions');
            if ($this->Head) {
                $this->addJsFile('discussions.js');
                $this->Head->AddRss($this->SelfUrl.'/feed.rss', $this->Head->title());
            }

            // Set CategoryID
            $CategoryID = val('CategoryID', $Category);
            $this->setData('CategoryID', $CategoryID, true);

            // Add modules
            $this->addModule('NewDiscussionModule');
            $this->addModule('DiscussionFilterModule');
            $this->addModule('CategoriesModule');
            $this->addModule('BookmarkedModule');

            // Get a DiscussionModel
            $DiscussionModel = new DiscussionModel();
            $CategoryIDs = array($CategoryID);
            if (c('Vanilla.ExpandCategories')) {
                $CategoryIDs = array_merge($CategoryIDs, array_column($this->data('Categories'), 'CategoryID'));
            }
            $Wheres = array('d.CategoryID' => $CategoryIDs);
            $this->setData('_ShowCategoryLink', count($CategoryIDs) > 1);

            // Check permission
            $this->permission('Vanilla.Discussions.View', true, 'Category', val('PermissionCategoryID', $Category));

            // Set discussion meta data.
            $this->EventArguments['PerPage'] = c('Vanilla.Discussions.PerPage', 30);
            $this->fireEvent('BeforeGetDiscussions');
            list($Offset, $Limit) = offsetLimit($Page, $this->EventArguments['PerPage']);
            if (!is_numeric($Offset) || $Offset < 0) {
                $Offset = 0;
            }

            $Page = PageNumber($Offset, $Limit);

            // Allow page manipulation
            $this->EventArguments['Page'] = &$Page;
            $this->EventArguments['Offset'] = &$Offset;
            $this->EventArguments['Limit'] = &$Limit;
            $this->fireEvent('AfterPageCalculation');

            // We want to limit the number of pages on large databases because requesting a super-high page can kill the db.
            $MaxPages = c('Vanilla.Categories.MaxPages');
            if ($MaxPages && $Page > $MaxPages) {
                throw notFoundException();
            }

            $CountDiscussions = $DiscussionModel->getCount($Wheres);
            if ($MaxPages && $MaxPages * $Limit < $CountDiscussions) {
                $CountDiscussions = $MaxPages * $Limit;
            }

            $this->setData('CountDiscussions', $CountDiscussions);
            $this->setData('_Limit', $Limit);

            // We don't wan't child categories in announcements.
            $Wheres['d.CategoryID'] = $CategoryID;
            $AnnounceData = $Offset == 0 ? $DiscussionModel->GetAnnouncements($Wheres) : new Gdn_DataSet();
            $this->setData('AnnounceData', $AnnounceData, true);
            $Wheres['d.CategoryID'] = $CategoryIDs;

            $this->DiscussionData = $this->setData('Discussions', $DiscussionModel->getWhere($Wheres, $Offset, $Limit));

            // Build a pager
            $PagerFactory = new Gdn_PagerFactory();
            $this->EventArguments['PagerType'] = 'Pager';
            $this->fireEvent('BeforeBuildPager');
            $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
            $this->Pager->ClientID = 'Pager';
            $this->Pager->configure(
                $Offset,
                $Limit,
                $CountDiscussions,
                array('CategoryUrl')
            );
            $this->Pager->Record = $Category;
            PagerModule::Current($this->Pager);
            $this->setData('_Page', $Page);
            $this->setData('_Limit', $Limit);
            $this->fireEvent('AfterBuildPager');

            // Set the canonical Url.
            $this->canonicalUrl(CategoryUrl($Category, PageNumber($Offset, $Limit)));

            // Change the controller name so that it knows to grab the discussion views
            $this->ControllerName = 'DiscussionsController';
            // Pick up the discussions class
            $this->CssClass = 'Discussions Category-'.GetValue('UrlCode', $Category);

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
     * @since 2.0.17
     * @access public
     */
    public function all($Category = '') {
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
            $this->Description(c('Garden.Description', null));
        }

        $this->setData('Breadcrumbs', CategoryModel::GetAncestors(val('CategoryID', $this->data('Category'))));

        // Set the category follow toggle before we load category data so that it affects the category query appropriately.
        $CategoryFollowToggleModule = new CategoryFollowToggleModule($this);
        $CategoryFollowToggleModule->SetToggle();

        // Get category data
        $this->CategoryModel->Watching = !Gdn::session()->GetPreference('ShowAllCategories');

        if ($Category) {
            $Subtree = CategoryModel::GetSubtree($Category, false);
            $CategoryIDs = consolidateArrayValuesByKey($Subtree, 'CategoryID');
            $Categories = $this->CategoryModel->GetFull($CategoryIDs)->resultArray();
        } else {
            $Categories = $this->CategoryModel->GetFull()->resultArray();
        }
        $this->setData('Categories', $Categories);

        // Add modules
        $this->addModule('NewDiscussionModule');
        $this->addModule('DiscussionFilterModule');
        $this->addModule('BookmarkedModule');
        $this->addModule($CategoryFollowToggleModule);

        $this->canonicalUrl(url('/categories', true));

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
            $this->Description(c('Garden.Description', null));
        }

        Gdn_Theme::section('CategoryDiscussionList');

        // Set the category follow toggle before we load category data so that it affects the category query appropriately.
        $CategoryFollowToggleModule = new CategoryFollowToggleModule($this);
        $CategoryFollowToggleModule->SetToggle();

        $this->CategoryModel->Watching = !Gdn::session()->GetPreference('ShowAllCategories');

        if ($Category) {
            $Subtree = CategoryModel::GetSubtree($Category, false);
            $CategoryIDs = consolidateArrayValuesByKey($Subtree, 'CategoryID');
            $Categories = $this->CategoryModel->GetFull($CategoryIDs)->resultArray();
        } else {
            $Categories = $this->CategoryModel->GetFull()->resultArray();
        }
        $this->setData('Categories', $Categories);

        // Get category data and discussions
        $this->DiscussionsPerCategory = c('Vanilla.Discussions.PerCategory', 5);
        $DiscussionModel = new DiscussionModel();
        $this->CategoryDiscussionData = array();
        foreach ($this->CategoryData->result() as $Category) {
            if ($Category->CategoryID > 0) {
                $this->CategoryDiscussionData[$Category->CategoryID] = $DiscussionModel->get(0, $this->DiscussionsPerCategory, array('d.CategoryID' => $Category->CategoryID, 'Announce' => 'all'));
            }
        }

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

    public function __get($Name) {
        switch ($Name) {
            case 'CategoryData':
//            Deprecated('CategoriesController->CategoryData', "CategoriesController->data('Categories')");
                $this->CategoryData = new Gdn_DataSet($this->data('Categories'), DATASET_TYPE_ARRAY);
                $this->CategoryData->DatasetType(DATASET_TYPE_OBJECT);
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
            redirect('/discussions');
        }
        if ($this->Menu) {
            $this->Menu->highlightRoute('/categories');
        }

        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);
    }
}
