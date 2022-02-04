<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Contracts\ConfigurationInterface;

use Vanilla\Web\Robots;

/**
 * Class SitemapsPlugin
 */
class SitemapsPlugin extends Gdn_Plugin {
    /**
     * This is the name of the feature flag to enable site maps that point directly to discussions.
     */
    const DISCUSSION_SITE_MAPS = 'discussionSiteMaps';

    /**
     * @var \Garden\EventManager
     */
    private $eventManager;

    /** @var bool */
    private $isSitePrivate;

    /**
     * @var int
     */
    private $discussionLimit = 1000;

    /**
     * SitemapsPlugin constructor.
     *
     * @param \Garden\EventManager $eventManager
     * @param ConfigurationInterface $config
     */
    public function __construct(\Garden\EventManager $eventManager, ConfigurationInterface $config) {
        parent::__construct();
        $this->eventManager = $eventManager;
        $this->isSitePrivate = $config->get('Garden.PrivateCommunity', false);
    }

    /// Methods ///

    /**
     * Build a site map for a category that points to the category archive pages.
     *
     * @param string $urlCode The URL code of the category.
     * @param array $urls An array to collect URLs.
     * @throws Exception Not found exception.
     */
    public function buildCategoryArchiveSiteMap($urlCode, &$urls) {
        $category = CategoryModel::categories($urlCode);
        if (!$category) {
            throw notFoundException();
        }

        // Get the min/max dates for the sitemap.
        $row = Gdn::sql()
            ->select('DateInserted', 'min', 'MinDate')
            ->select('DateInserted', 'max', 'MaxDate')
            ->from('Discussion')
            ->where('CategoryID', $category['CategoryID'])
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        if ($row) {
            $from = strtotime('first day of this month 00:00:00', strtotime($row['MaxDate']));
            $to = strtotime('first day of this month 00:00:00', strtotime($row['MinDate']));

            if (!$from || !$to) {
                $from = -1;
                $to = 0;
            }
        } else {
            $from = -1;
            $to = 0;
        }

        $now = time();

        for ($i = $from; $i >= $to; $i = strtotime('-1 month', $i)) {
            $url = [
                'Loc' => url('/categories/archives/'.rawurlencode($category['UrlCode'] ? $category['UrlCode'] : $category['CategoryID']).'/'.gmdate('Y-m', $i), true),
                'LastMod' => '',
                'ChangeFreq' => ''
            ];

            $lastMod = strtotime('last day of this month', $i);
            if ($lastMod > $now) {
                $lastMod = $now;
            }
            $url['LastMod'] = gmdate('c', $lastMod);

            $urls[] = $url;
        }

        // If there are no links then just link to the category.
        if (count($urls) === 0) {
            $url = [
                'Loc' => categoryUrl($category),
                'LastMod' => '',
                'ChangeFreq' => ''
            ];
            $urls[] = $url;
        }
    }

    /**
     * Build a site map that directly points to the discussions in a category.
     *
     * @param string $filename The filename of the category in the format: "urlCode-start-finish.xml"
     * @param array $urls An array to collect the resulting Urls.
     */
    private function buildCategoryDiscussionsSiteMap(string $filename, array &$urls) {
        $matched = preg_match('`^(.+)-(\d+)-(\d+)$`', $filename, $m);
        if (!$matched) {
            throw notFoundException();
        }
        $urlCode = $m[1];
        $offset = (int)max($m[2] - 1, 0);
        $limit = (int)min($m[3], 5000);

        $category = CategoryModel::categories($urlCode);
        $countDiscussions = $category['CountDiscussions'] ?? $category['countDiscussions'] ?? null;
        if (!$category || $offset > $countDiscussions) {
            throw notFoundException();
        }
        if (!$category['PermsDiscussionsView']) {
            throw permissionException('discussions.view');
        }

        /* @var \DiscussionModel $model */
        $model = Gdn::getContainer()->get(\DiscussionModel::class);

        $discussions = $model->getWhereRecent(['CategoryID' => $category['CategoryID']], $limit, $offset, false);
        foreach ($discussions as $discussion) {
            $url = [
                'Loc' => discussionUrl($discussion),
                'LastMod' => gmdate('c', Gdn_Format::toTimestamp($discussion->DateUpdated ?: $discussion->DateInserted)),
            ];

            $urls[] = $url;
        }

        // If there are no links then just link to the category.
        // This just ensures that the sitemap does not appear as an error to crawlers.
        if (count($urls) === 0) {
            $url = [
                'Loc' => categoryUrl($category),
                'LastMod' => '',
            ];
            $urls[] = $url;
        }
    }

    /**
     * Run once on enable.
     *
     * @return void
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Run on utility/update.
     *
     * @return void
     */
    public function structure() {
        Gdn::router()->setRoute('sitemapindex.xml', '/utility/sitemapindex.xml', 'Internal');
        Gdn::router()->setRoute('sitemap-(.+)', '/utility/sitemap/$1', 'Internal');
    }

    /**
     *  Render settings page
     *
     * @param SettingsController $sender
     */
    public function settingsController_sitemaps_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Sitemap Settings'));
        $sender->setData('isSitePrivate', $this->isSitePrivate);
        $sender->addSideMenu();

        $configurationModule = new ConfigurationModule($sender);
        $configurationModule->initialize([
            'Feature.discussionSiteMaps.Enabled' => [
                'LabelCode' => t('Discussion Based Sitemaps', 'Discussion Based Sitemaps (BETA)'),
                'Control' => 'Toggle',
                'Description' => t('Use the sitemaps that point directly to discussions instead of categories.'),
            ]]);
        $sender->setData('ConfigurationModule', $configurationModule);
        $sender->render('Settings', '', 'plugins/Sitemaps');
    }

    /**
     * Hook into the site's robots.txt generation.
     *
     * @param Robots $robots
     */
    public function robots_init(Robots $robots) {
        $robots->addSitemap("/sitemapindex.xml");
    }

    /**
     *  Render sitemaps index
     *
     * @param UtilityController $sender Sending controller instance
     */
    public function utilityController_siteMapIndex_create($sender) {
        if (\Vanilla\FeatureFlagHelper::featureEnabled(static::DISCUSSION_SITE_MAPS)) {
            $this->renderSiteMapIndex($sender);
        } else {
            $this->renderSitemapIndexOld($sender);
        }
    }

    /**
     * Render the category site map index.
     *
     * This method is called if the DISCUSSION_SITE_MAPS is set.
     *
     * @param Gdn_Controller $sender The controller doing the render.
     */
    private function renderSiteMapIndex(Gdn_Controller $sender) {
        // Clear the session to mimic a crawler.
        Gdn::session()->start(0, false, false);
        $sender->deliveryMethod(DELIVERY_METHOD_XHTML);
        $sender->deliveryType(DELIVERY_TYPE_VIEW);
        $sender->setHeader('Content-Type', 'text/xml');

        $SiteMaps = [];

        if (class_exists('CategoryModel')) {
            $Categories = CategoryModel::categories();

            $this->EventArguments['Categories'] = &$categories;
            $this->fireEvent('siteMapCategories');

            $limit = $this->getDiscussionLimit();

            foreach ($Categories as $Category) {
                if (!$Category['PermsDiscussionsView'] || $Category['CategoryID'] < 0 || $Category['CountDiscussions'] == 0) {
                    continue;
                }

                $urlCode = rawurlencode($Category['UrlCode'] ? $Category['UrlCode'] : $Category['CategoryID']);

                /**
                 * Add several site-maps for each page of discussions.
                 */
                for ($i = 0; $i < $Category['CountDiscussions']; $i += $limit) {
                    $SiteMap = [
                        'Loc' => url('/sitemap-category-'.$urlCode.'-'.($i + 1).'-'.($i + $limit).'.xml', true),
                        'ChangeFreq' => '',
                        'Priority' => '',
                    ];

                    if ($i === 0) {
                        $SiteMap['LastMod'] = $Category['DateLastComment'];
                    }
                    $SiteMaps[] = $SiteMap;
                }
            }
        }
        $sender->setData('SiteMaps', $SiteMaps);
        $sender->render('SiteMapIndex', '', 'plugins/Sitemaps');
    }

    /**
     * This is the old way of rendering the site map index.
     *
     * This method is currently invoked if the `DISCUSSION_SITE_MAPS` feature is not set.
     *
     * @param UtilityController $sender Sending controller instance
     */
    private function renderSiteMapIndexOld($sender) {
        // Clear the session to mimic a crawler.
        Gdn::session()->start(0, false, false);
        $sender->deliveryMethod(DELIVERY_METHOD_XHTML);
        $sender->deliveryType(DELIVERY_TYPE_VIEW);
        $sender->setHeader('Content-Type', 'text/xml');

        $SiteMaps = [];

        if (class_exists('CategoryModel')) {
            $Categories = CategoryModel::categories();

            $this->EventArguments['Categories'] = &$categories;
            $this->fireEvent('siteMapCategories');

            foreach ($Categories as $Category) {
                if (!$Category['PermsDiscussionsView'] || $Category['CategoryID'] < 0 || $Category['CountDiscussions'] == 0) {
                    continue;
                }

                $SiteMap = [
                    'Loc' => url('/sitemap-category-'.rawurlencode($Category['UrlCode'] ? $Category['UrlCode'] : $Category['CategoryID']).'.xml', true),
                    'LastMod' => $Category['DateLastComment'],
                    'ChangeFreq' => '',
                    'Priority' => ''
                ];
                $SiteMaps[] = $SiteMap;
            }
        }
        $sender->setData('SiteMaps', $SiteMaps);
        $sender->render('SiteMapIndex', '', 'plugins/Sitemaps');
    }

    /**
     * Build sitemaps index
     *
     * @param UtilityController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function utilityController_siteMap_create($sender, $args) {
        Gdn::session()->start(0, false, false);
        $sender->deliveryMethod(DELIVERY_METHOD_XHTML);
        $sender->deliveryType(DELIVERY_TYPE_VIEW);
        $sender->setHeader('Content-Type', 'text/xml');

        $filename = $args[0] ?? '';
        if (substr($filename, -4) === '.xml') {
            $filename = substr($filename, 0, -4);
        }

        list($type, $arg) = explode('-', $filename, 2) + ['', ''];

        $urls = [];
        switch ($type) {
            case 'category':
                // Build the category site map.
                if (\Vanilla\FeatureFlagHelper::featureEnabled(static::DISCUSSION_SITE_MAPS) && preg_match('`\d+-\d+$`', $arg)) {
                    $this->buildCategoryDiscussionsSiteMap($arg, $urls);
                } else {
                    $this->buildCategoryArchiveSiteMap($arg, $urls);
                }
                break;
            default:
                // See if a plugin can build the sitemap.
                $this->EventArguments['Type'] = $type;
                $this->EventArguments['Arg'] = $arg;
                $this->EventArguments['Urls'] =& $urls;
                $this->fireEvent('SiteMap'.ucfirst($type));
                break;
        }

        $sender->setData('Urls', $urls);
        $sender->render('SiteMap', '', 'plugins/Sitemaps');
    }

    /**
     * Get discussion limit
     * @return int
     */
    public function getDiscussionLimit(): int {
        return $this->discussionLimit;
    }

    /**
     * Set discussion limit
     * @param int $discussionLimit
     */
    public function setDiscussionLimit(int $discussionLimit) {
        $this->discussionLimit = $discussionLimit;
        return $this;
    }
}
