<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Contracts\ConfigurationInterface;

use Vanilla\Permissions;
use Vanilla\Web\Robots;

/**
 * Class SitemapsPlugin
 */
class SitemapsPlugin extends Gdn_Plugin
{
    private CategoryModel $categoryModel;

    /**
     * @var int
     */
    private $discussionLimit = 1000;

    /**
     * SitemapsPlugin constructor.
     *
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel)
    {
        parent::__construct();
        $this->categoryModel = $categoryModel;
    }

    /// Methods ///

    /**
     * Build a site map to point to list top level home pages
     *
     * @param array $urls
     * @return void
     */
    private function buildHomePageSiteMap(array &$urls)
    {
        $siteSectionModel = \gdn::getContainer()->get(\Vanilla\Site\SiteSectionModel::class);
        foreach ($siteSectionModel->getAll() as $siteSection) {
            if (!empty($siteSection->getBasePath() && !empty($siteSection->getCategoryID()))) {
                $canView = Gdn::session()->checkPermission(
                    ["discussions.view"],
                    true,
                    "Category",
                    $siteSection->getCategoryID(),
                    Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION
                );

                if (!$canView) {
                    continue;
                }
            }
            $urls[] = ["Loc" => \Gdn::request()->getSimpleUrl($siteSection->getBasePath())];
        }
    }

    /**
     * Build sitemap for categories
     *
     * @param string $pager
     * @param array $urls
     * @return void
     */
    private function buildCategorySiteMap(string $pager, array &$urls)
    {
        $categories = $this->categoryModel->getVisibleCategories([
            "forceArrayReturn" => true,
            "filterNonPostableCategories" => true,
            "filterNonDiscussionCategories" => true,
        ]);

        [$min, $max] = explode("-", $pager);
        if (empty($min) || empty($max)) {
            throw notFoundException();
        }
        if (!isset($categories[$max])) {
            $max = count($categories);
        }

        for ($i = $min - 1; $i < $max; $i++) {
            $category = $categories[$i];
            $url = [];
            $discussionPageLimit = Gdn::config()->get("Vanilla.Discussions.PerPage", 30);
            for ($j = 1; $j <= min(ceil($category["CountDiscussions"] / $discussionPageLimit), 100); $j++) {
                $url = [];
                $url["Loc"] = $category["Url"] . ($j > 1 ? "/p{$j}" : "");
                if ($j === 1) {
                    $lastModifiedDate =
                        isset($category["DateLastComment"]) && $category["DateLastComment"] > $category["DateUpdated"]
                            ? $category["DateLastComment"]
                            : $category["DateUpdated"];
                    $url["LastMod"] = $lastModifiedDate;
                }
                $urls[] = $url;
            }
        }
    }

    /**
     * Build a site map that directly points to the discussions in a category.
     *
     * @param string $filename The filename of the category in the format: "urlCode-start-finish.xml"
     * @param array $urls An array to collect the resulting Urls.
     */
    private function buildCategoryDiscussionsSiteMap(string $filename, array &$urls)
    {
        $matched = preg_match('`^(.+)-(\d+)-(\d+)$`', $filename, $m);
        if (!$matched) {
            throw notFoundException();
        }
        $urlCode = $m[1];
        $offset = (int) max($m[2] - 1, 0);
        $limit = (int) min($m[3], 5000);

        $category = CategoryModel::categories($urlCode);
        $countDiscussions = $category["CountDiscussions"] ?? ($category["countDiscussions"] ?? null);
        if (!$category || $offset > $countDiscussions) {
            throw notFoundException();
        }
        if (!$category["PermsDiscussionsView"]) {
            throw permissionException("discussions.view");
        }

        /* @var \DiscussionModel $model */
        $discussions = $this->selectRecentDiscussionForCategory($category["CategoryID"], $limit, $offset);
        foreach ($discussions as $discussion) {
            $lastModified = $discussion->DateLastComment;
            if ($discussion->DateUpdated && $discussion->DateUpdated > $discussion->DateLastComment) {
                $lastModified = $discussion->DateUpdated;
            }
            $url = [
                "Loc" => discussionUrl($discussion),
                "LastMod" => gmdate("c", Gdn_Format::toTimestamp($lastModified)),
            ];

            $urls[] = $url;
        }

        // If there are no links then just link to the category.
        // This just ensures that the sitemap does not appear as an error to crawlers.
        if (count($urls) === 0) {
            $url = [
                "Loc" => categoryUrl($category),
                "LastMod" => "",
            ];
            $urls[] = $url;
        }
    }

    /**
     * Run on utility/update.
     *
     * @return void
     */
    public function structure()
    {
        Gdn::router()->setRoute("sitemapindex.xml", "/utility/sitemapindex.xml", "Internal");
        Gdn::router()->setRoute("sitemap-(.+)", '/utility/sitemap/$1', "Internal");
    }

    /**
     * Hook into the site's robots.txt generation.
     *
     * @param Robots $robots
     */
    public function robots_init(Robots $robots)
    {
        $robots->addSitemap("/sitemapindex.xml");
    }

    /**
     *  Render sitemaps index
     *
     * @param UtilityController $sender Sending controller instance
     */
    public function utilityController_siteMapIndex_create($sender)
    {
        $this->renderSiteMapIndex($sender);
    }

    /**
     * Render the category site map index.
     *
     * @param Gdn_Controller $sender The controller doing the render.
     */
    private function renderSiteMapIndex(Gdn_Controller $sender)
    {
        // Clear the session to mimic a crawler.
        Gdn::session()->start(0, false, false);
        $sender->deliveryMethod(DELIVERY_METHOD_XHTML);
        $sender->deliveryType(DELIVERY_TYPE_VIEW);
        $sender->setHeader("Content-Type", "text/xml");

        $siteMaps = [];

        //home page sitemap
        $siteMaps[] = [
            "Loc" => url("/sitemap-homepages.xml", true),
        ];

        // Sitemap Categories
        if (class_exists("CategoryModel")) {
            // Get all available categories for the specific user
            $options = [
                "filterNonPostableCategories" => true,
                "forceArrayReturn" => true,
                "filterNonDiscussionCategories" => true,
            ];
            $availableCategories = $this->categoryModel->getVisibleCategories($options);
            $availableCategoryCount = count($availableCategories);
            $maxPage = $this->categoryModel->getMaxPages();
            $totalPages = ceil($availableCategoryCount / $maxPage);
            $start = 1;
            $end = $maxPage;
            for ($i = 1; $i <= min($totalPages, 100); $i++) {
                $siteMaps[] = [
                    "Loc" => url("/sitemap-categories-{$start}-{$end}.xml", true),
                ];
                $start = $end + 1;
                $end = $end + $maxPage;
            }

            $categories = CategoryModel::categories();

            $this->EventArguments["Categories"] = &$categories;
            $this->fireEvent("siteMapCategories");

            $limit = $this->getDiscussionLimit();

            foreach ($categories as $category) {
                if (
                    !$category["PermsDiscussionsView"] ||
                    $category["CategoryID"] < 0 ||
                    $category["CountDiscussions"] == 0
                ) {
                    continue;
                }

                $urlCode = rawurlencode($category["UrlCode"] ? $category["UrlCode"] : $category["CategoryID"]);

                /**
                 * Add several site-maps for each page of discussions.
                 */
                for ($i = 0; $i < $category["CountDiscussions"]; $i += $limit) {
                    $siteMap = [
                        "Loc" => url(
                            "/sitemap-category-" . $urlCode . "-" . ($i + 1) . "-" . ($i + $limit) . ".xml",
                            true
                        ),
                        "ChangeFreq" => "",
                        "Priority" => "",
                    ];

                    if ($i === 0) {
                        $siteMap["LastMod"] = $category["DateLastComment"] ?? "";
                    }
                    $siteMaps[] = $siteMap;
                }
            }
        }
        $sender->setData("SiteMaps", $siteMaps);
        $sender->render("SiteMapIndex", "", "plugins/Sitemaps");
    }

    /**
     * Build sitemaps index
     *
     * @param UtilityController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function utilityController_siteMap_create($sender, $args)
    {
        Gdn::session()->start(0, false, false);
        $sender->deliveryMethod(DELIVERY_METHOD_XHTML);
        $sender->deliveryType(DELIVERY_TYPE_VIEW);
        $sender->setHeader("Content-Type", "text/xml");

        $filename = $args[0] ?? "";
        if (substr($filename, -4) === ".xml") {
            $filename = substr($filename, 0, -4);
        }

        [$type, $arg] = explode("-", $filename, 2) + ["", ""];

        $urls = [];
        switch ($type) {
            case "category":
                // Build the category site map.
                if (preg_match('`\d+-\d+$`', $arg)) {
                    $this->buildCategoryDiscussionsSiteMap($arg, $urls);
                }
                break;
            case "homepages":
                $this->buildHomePageSiteMap($urls);
                break;
            case "categories":
                $this->buildCategorySiteMap($arg, $urls);
                break;
            default:
                // See if a plugin can build the sitemap.
                $this->EventArguments["Type"] = $type;
                $this->EventArguments["Arg"] = $arg;
                $this->EventArguments["Urls"] = &$urls;
                $this->fireEvent("SiteMap" . ucfirst($type));
                break;
        }

        $sender->setData("Urls", $urls);
        $sender->render("SiteMap", "", "plugins/Sitemaps");
    }

    /**
     * Get discussion limit
     * @return int
     */
    public function getDiscussionLimit(): int
    {
        return $this->discussionLimit;
    }

    /**
     * Set discussion limit
     * @param int $discussionLimit
     */
    public function setDiscussionLimit(int $discussionLimit)
    {
        $this->discussionLimit = $discussionLimit;
        return $this;
    }

    /**
     * Get a simple list of recent discussion based on their categoryID
     *
     * @param int $categoryID
     * @return array|null
     */
    private function selectRecentDiscussionForCategory(int $categoryID, int $limit = 5000, int $offset = 0): ?array
    {
        $discussionModel = Gdn::getContainer()->get(\DiscussionModel::class);
        $where = ["CategoryID" => $categoryID];

        $innerSelect = $discussionModel->Database
            ->createSql()
            ->from("Discussion")
            ->select(["DiscussionID"])
            ->where($where)
            ->orderBy("DateLastComment", "desc")
            ->limit($limit, $offset)
            ->getSelect(true);

        $discussions = $discussionModel->Database
            ->createSql()
            ->select(["DiscussionID", "CategoryID", "Name", "DateLastComment", "DateUpdated"])
            ->from("Discussion d")
            ->join("({$innerSelect}) d2", "d.DiscussionID = d2.DiscussionID")
            ->get()
            ->resultArray();
        return $discussions;
    }
}
