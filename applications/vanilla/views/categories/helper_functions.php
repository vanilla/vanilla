<?php
if (!defined("APPLICATION")) {
    exit();
}

use Vanilla\Forum\Modules\FoundationCategoriesShim;
use Vanilla\Theme\BoxThemeShim;
use Vanilla\Utility\HtmlUtils;
use Vanilla\Web\TwigStaticRenderer;

if (!function_exists("CategoryHeading")):
    /**
     * Write the category heading in a category table.
     * Good for plugins that want to override whats displayed in the heading to the category name.
     *
     * @return string
     * @since 2.1
     */
    function categoryHeading()
    {
        return t("Categories");
    }
endif;

if (!function_exists("CategoryPhoto")):
    /**
     *
     * @since 2.1
     */
    function categoryPhoto($row)
    {
        $photoUrl = val("PhotoUrl", $row);

        if ($photoUrl) {
            $result = anchor(
                '<img src="' .
                    $photoUrl .
                    '" class="CategoryPhoto" height="200" width="200" alt="' .
                    htmlspecialchars(val("Name", $row, "")) .
                    '" />',
                categoryUrl($row, "", "//"),
                "Item-Icon PhotoWrap PhotoWrap-Category"
            );
        } else {
            $result = anchor(
                '<span class="sr-only">' . t("Expand for more options.") . "</span>",
                categoryUrl($row, "", "//"),
                "Item-Icon PhotoWrap PhotoWrap-Category Hidden NoPhoto"
            );
        }

        return $result;
    }
endif;

if (!function_exists("CategoryString")):
    function categoryString($rows)
    {
        $result = "";
        foreach ($rows as $row) {
            if ($result) {
                $result .= '<span class="Comma">, </span>';
            }
            $result .= anchor(htmlspecialchars($row["Name"]), $row["Url"]);
        }
        return $result;
    }
endif;

if (!function_exists("getOptions")):
    /**
     * Render options that the user has for this category. Returns an empty string if the session isn't valid.
     *
     * @param $category The category to render the options for.
     * @return DropdownModule|string A dropdown with the category options or an empty string if the session is not valid.
     * @throws Exception
     */
    function getOptions($category)
    {
        if (!Gdn::session()->isValid()) {
            return "";
        }
        $sender = Gdn::controller();
        $categoryID = val("CategoryID", $category);

        $dropdown = new DropdownModule("dropdown", "", "OptionsMenu");
        $tk = urlencode(Gdn::session()->transientKey());
        $followed = val("Followed", $category);

        $dropdown->addLink(t("Mark Read"), "/category/markread?categoryid={$categoryID}&tkey={$tk}", "mark-read");

        if (
            c(\CategoryModel::CONF_CATEGORY_FOLLOWING) &&
            val("DisplayAs", $category) == "Discussions" &&
            // Only social groups have the "Allow Groups" field
            val("AllowGroups", $category) < 1
        ) {
            $dropdown->addLink(
                t($followed ? "Unfollow" : "Follow"),
                "/category/followed?tkey={$tk}&categoryid={$categoryID}&value=" . ($followed ? 0 : 1),
                "hide"
            );
        }

        // Allow plugins to add options
        $sender->EventArguments["CategoryOptionsDropdown"] = &$dropdown;
        $sender->EventArguments["Category"] = &$category;
        $sender->fireEvent("CategoryOptionsDropdown");

        return $dropdown;
    }
endif;

if (!function_exists("MostRecentString")):
    function mostRecentString($row, $options = [])
    {
        $options = (array) $options + [
            "showUser" => true,
            "showDate" => true,
        ];

        if (!$row["LastTitle"]) {
            return "";
        }

        $r = "";

        $r .= '<span class="MostRecent">';
        $r .= '<span class="MLabel">' . t("Most recent:") . "</span> ";
        $r .= anchor(sliceString(Gdn_Format::text($row["LastTitle"]), 150), $row["LastUrl"], "LatestPostTitle");

        if ($options["showUser"] && val("LastName", $row)) {
            $r .= " ";

            $r .= '<span class="MostRecentBy">' . t("by") . " ";
            $r .= userAnchor($row, "UserLink", "Last");
            $r .= "</span>";
        }

        if ($options["showDate"] && val("LastDateInserted", $row)) {
            $r .= " ";

            $r .= '<span class="MostRecentOn"><span class="CommentDate">';
            $r .= Gdn_Format::date($row["LastDateInserted"], "html");
            $r .= "</span></span>";
        }

        $r .= "</span>";

        return $r;
    }
endif;

if (!function_exists("writeListItem")):
    /**
     * Renders a list item in a category list (modern view).
     *
     * @param $category
     * @param $depth
     * @throws Exception
     */
    function writeListItem($category, $depth)
    {
        $children = $category["Children"];
        $categoryID = val("CategoryID", $category);
        $cssClass = cssClass($category, true);
        $writeChildren = getWriteChildrenMethod($category, $depth);
        $rssIcon = "";
        $headingLevel = $depth + 2;
        /** @var Vanilla\Formatting\Html\HtmlSanitizer */
        $htmlSanitizer = Gdn::getContainer()->get(Vanilla\Formatting\Html\HtmlSanitizer::class);

        if (val("DisplayAs", $category) === "Discussions") {
            $rssImage = img("applications/dashboard/design/images/rss.gif", ["alt" => t("RSS Feed")]);
            $rssIcon = anchor($rssImage, "/categories/" . val("UrlCode", $category) . "/feed.rss", "", [
                "title" => t("RSS Feed"),
            ]);
        }

        if (val("DisplayAs", $category) === "Heading"): ?>
            <li id="Category_<?php echo $categoryID; ?>" class="CategoryHeading pageHeadingBox <?php echo $cssClass; ?>">
                <div role="heading" aria-level="<?php echo $headingLevel; ?>" class="ItemContent Category">
                    <div class="Options"><?php echo getOptions($category); ?></div>
                    <?php
                    echo Gdn_Format::text(val("Name", $category));
                    Gdn::controller()->EventArguments["ChildCategories"] = &$children;
                    Gdn::controller()->EventArguments["Category"] = &$category;
                    Gdn::controller()->fireEvent("AfterCategoryHeadingTitle");
                    ?>
                </div>
            </li>
        <?php else: ?>
            <li id="Category_<?php echo $categoryID; ?>" class="<?php echo $cssClass; ?> pageBox">
                <?php
                Gdn::controller()->EventArguments["ChildCategories"] = &$children;
                Gdn::controller()->EventArguments["Category"] = &$category;
                Gdn::controller()->fireEvent("BeforeCategoryItem");
                $headingClass = "CategoryNameHeading";
                if (empty($category["Description"])) {
                    $headingClass .= " isEmptyDescription";
                }
                ?>
                <div class="ItemContent Category">
                    <div class="Options">
                        <?php echo getOptions($category); ?>
                    </div>
                    <?php echo categoryPhoto($category); ?>
                    <div role="heading" aria-level="<?php echo $headingLevel; ?>" class="TitleWrap <?php echo $headingClass; ?>">
                        <?php
                        echo anchor(Gdn_Format::text(val("Name", $category)), categoryUrl($category), "Title");
                        Gdn::controller()->fireEvent("AfterCategoryTitle");
                        ?>
                    </div>
                    <div class="CategoryDescription">
                        <?php echo $htmlSanitizer->filter((string) val("Description", $category, "")); ?>
                    </div>
                    <div class="Meta">
                        <span class="MItem RSS"><?php echo $rssIcon; ?></span>
                        <span class="MItem DiscussionCount">
                            <?php echo sprintf(
                                pluralTranslate(
                                    val("CountAllDiscussions", $category),
                                    "%s discussion html",
                                    "%s discussions html",
                                    t("%s discussion"),
                                    t("%s discussions")
                                ),
                                bigPlural(val("CountAllDiscussions", $category), "%s discussion")
                            ); ?>
                        </span>
                        <span class="MItem CommentCount">
                            <?php echo sprintf(
                                pluralTranslate(
                                    val("CountAllComments", $category),
                                    "%s comment html",
                                    "%s comments html",
                                    t("%s comment"),
                                    t("%s comments")
                                ),
                                bigPlural(val("CountAllComments", $category), "%s comment")
                            ); ?>
                        </span>

                        <?php
                        if (val("LastTitle", $category) != ""): ?>
                            <span class="MItem LastDiscussionTitle">
                                <?php echo mostRecentString($category, ["showDate" => false]); ?>
                            </span>
                            <span class="MItem LastCommentDate">
                                <?php echo Gdn_Format::date(val("LastDateInserted", $category)); ?>
                            </span>
                        <?php endif;
                        if ($writeChildren === "list"): ?>
                            <div class="ChildCategories">
                                <?php echo wrap(t("Child Categories") . ": ", "b"); ?>
                                <?php echo categoryString($children, $depth + 1); ?>
                            </div>
                        <?php endif;
                        ?>
                    </div>
                </div>
            </li>
        <?php endif;
        if ($writeChildren === "items") {
            foreach ($children as $child) {
                writeListItem($child, $depth + 1);
            }
        }
    }
endif;

if (!function_exists("WriteTableHead")):
    function writeTableHead()
    {
        ?>
        <tr>
            <td class="CategoryName" role="columnheader">
                <div class="Wrap"><?php echo categoryHeading(); ?></div>
            </td>
            <td class="BigCount CountDiscussions" role="columnheader">
                <div class="Wrap"><?php echo t("Discussions"); ?></div>
            </td>
            <td class="BigCount CountComments" role="columnheader">
                <div class="Wrap"><?php echo t("Comments"); ?></div>
            </td>
            <td class="BlockColumn LatestPost">
                <div class="Wrap"><?php echo t("Latest Post"); ?></div>
            </td>
        </tr>
    <?php
    }
endif;

if (!function_exists("WriteTableRow")):
    function writeTableRow($row, $depth = 1)
    {
        $children = $row["Children"];
        $writeChildren = getWriteChildrenMethod($row, $depth);
        $h = "h" . ($depth + 1);
        $level = 3;
        /** @var Vanilla\Formatting\Html\HtmlSanitizer */
        $htmlSanitizer = Gdn::getContainer()->get(Vanilla\Formatting\Html\HtmlSanitizer::class);
        /** @var Vanilla\Formatting\DateTimeFormatter */
        $dateTimeFormatter = Gdn::getContainer()->get(\Vanilla\Formatting\DateTimeFormatter::class);
        ?>
        <tr class="<?php echo cssClass($row, true); ?>">
            <td class="CategoryName">
                <div class="Wrap">
                    <?php
                    echo '<div class="Options">' . getOptions($row) . "</div>";

                    echo categoryPhoto($row);

                    $headingClass = "CategoryNameHeading";
                    if (empty($row["Description"])) {
                        $headingClass .= " isEmptyDescription";
                    }

                    echo "<{$h} aria-level='" . $level . "' class='" . $headingClass . "'>";
                    $safeName = htmlspecialchars($row["Name"] ?? "");
                    echo $row["DisplayAs"] === "Heading" ? $safeName : anchor($safeName, $row["Url"]);
                    Gdn::controller()->EventArguments["Category"] = $row;
                    Gdn::controller()->fireEvent("AfterCategoryTitle");
                    echo "</{$h}>";
                    ?>
                    <div class="CategoryDescription">
                        <?php echo $htmlSanitizer->filter($row["Description"] ?? ""); ?>
                    </div>
                    <?php if ($writeChildren === "list"): ?>
                        <div class="ChildCategories">
                            <?php
                            echo wrap(t("Child Categories") . ": ", "b");
                            echo categoryString($children, $depth + 1);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="BigCount CountDiscussions">
                <div class="Wrap">
                    <?php //            echo "({$Row['CountDiscussions']})";

        echo bigPlural($row["CountAllDiscussions"], "%s discussion"); ?>
                </div>
            </td>
            <td class="BigCount CountComments">
                <div class="Wrap">
                    <?php //            echo "({$Row['CountComments']})";

        echo bigPlural($row["CountAllComments"], "%s comment"); ?>
                </div>
            </td>
            <td class="BlockColumn LatestPost">
                <div class="Block Wrap">
                    <?php if ($row["LastTitle"]): ?>
                        <?php
                        echo userPhoto($row, ["Size" => "Small", "Px" => "Last"]);
                        echo anchor(
                            sliceString(Gdn_Format::text($row["LastTitle"]), 100),
                            $row["LastUrl"],
                            "BlockTitle LatestPostTitle",
                            ["title" => html_entity_decode($row["LastTitle"])]
                        );
                        ?>
                        <div class="Meta">
                            <?php echo userAnchor($row, "UserLink MItem", "Last"); ?>
                            <span class="Bullet">•</span>
                            <?php
                            echo anchor(
                                Gdn_Format::date($row["LastDateInserted"], "html"),
                                $row["LastUrl"],
                                "CommentDate MItem",
                                [
                                    "aria-label" => HtmlUtils::accessibleLabel(
                                        'Most recent comment on date %s, in discussion "%s", by user "%s"',
                                        [
                                            $dateTimeFormatter->formatDate($row["LastDateInserted"], false),
                                            $row["Name"],
                                            $row["LastName"],
                                        ]
                                    ),
                                ]
                            );

                            if (!empty($row["LastCategoryID"])) {
                                $lastCategory = CategoryModel::categories($row["LastCategoryID"]);

                                if (is_array($lastCategory)) {
                                    echo " <span>",
                                        sprintf(
                                            "in %s",
                                            anchor(
                                                htmlspecialchars($lastCategory["Name"] ?? ""),
                                                categoryUrl($lastCategory, "", "//")
                                            )
                                        ),
                                        "</span>";
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php if ($writeChildren === "items") {
            foreach ($children as $childRow) {
                writeTableRow($childRow, $depth + 1);
            }
        }
    }
endif;

if (!function_exists("writeCategoryList")):
    /**
     * Renders a category list (modern view).
     *
     * @param $categories
     * @param int $depth
     */
    function writeCategoryList($categories, $depth = 1)
    {
        if (empty($categories)) {
            BoxThemeShim::startBox();
            echo '<div class="Empty">' . t("No categories were found.") . "</div>";
            BoxThemeShim::endBox();
            return;
        } ?>
        <h2 class="sr-only"><?php echo t("Category List"); ?></h2>
        <div class="DataListWrap">
            <?php if (FoundationCategoriesShim::isEnabled()) {
                FoundationCategoriesShim::printLegacyShim($categories);
            } else {
                echo '<ul class="DataList CategoryList pageBox">';
                foreach ($categories as $category) {
                    writeListItem($category, $depth);
                }
                echo "</ul>";
            } ?>
        </div>
        <?php
    }
endif;

if (!function_exists("writeCategoryTable")):
    function writeCategoryTable($categories, $depth = 1, $inTable = false)
    {
        if (empty($categories)) {
            BoxThemeShim::startBox();
            echo '<div class="Empty">' . t("No categories were found.") . "</div>";
            BoxThemeShim::endBox();
            return;
        }

        foreach ($categories as $category) {
            $displayAs = val("DisplayAs", $category);
            $urlCode = $category["UrlCode"];
            $class = val("CssClass", $category);
            $name = htmlspecialchars($category["Name"] ?? "");

            if ($displayAs === "Heading"):
                if ($inTable) {
                    echo "</tbody></table></div>";
                    $inTable = false;
                } ?>
                <div id="CategoryGroup-<?php echo $urlCode; ?>" class="CategoryGroup <?php echo $class; ?>">
                    <?php BoxThemeShim::startHeading(); ?>
                    <h2 class="H categoryList-heading"><?php echo $name; ?></h2>
                    <?php BoxThemeShim::endHeading(); ?>
                    <?php writeCategoryTable($category["Children"], $depth + 1, $inTable); ?>
                </div>
                <?php
            else:
                if (!$inTable) { ?>
                    <div class="DataTableWrap">
                    <h2 class="sr-only categoryList-genericHeading"><?php echo t("Category List"); ?></h2>
                    <table class="DataTable CategoryTable">
                            <thead>
                            <?php writeTableHead(); ?>
                            </thead>
                            <tbody>
                    <?php $inTable = true;}
                writeTableRow($category, $depth);
            endif;
        }
        if ($inTable) {
            echo "</tbody></table></div>";
        }
    }
endif;

if (!function_exists("getWriteChildrenMethod")):
    /**
     * Calculates how to display category children. Either 'list' for a comma-separated list (usually appears in meta) or
     * 'items' to nest children below the parent or false if there are no children.
     *
     * @param $category
     * @param $depth
     * @return bool|string
     */
    function getWriteChildrenMethod($category, $depth)
    {
        $children = val("Children", $category);
        $writeChildren = false;
        $maxDisplayDepth = c("Vanilla.Categories.MaxDisplayDepth");
        $isHeading = val("DisplayAs", $category) === "Heading";

        if (!empty($children)) {
            if (!$isHeading && $maxDisplayDepth > 0 && $depth + 1 >= $maxDisplayDepth) {
                $writeChildren = "list";
            } else {
                $writeChildren = "items";
            }
        }

        return $writeChildren;
    }
endif;

if (!function_exists("followButton")):
    /**
     *
     * Writes the Follow/following button
     *
     * @param int $categoryID
     * @return string
     */
    function followButton($categoryID)
    {
        $output = " ";
        if (!is_numeric($categoryID)) {
            return $output;
        }

        $userID = Gdn::session()->UserID;
        $category = CategoryModel::categories($categoryID);

        if (
            c(\CategoryModel::CONF_CATEGORY_FOLLOWING) &&
            $userID &&
            $category &&
            $category["DisplayAs"] == "Discussions"
        ) {
            $categoryModel = new CategoryModel();
            $following = $categoryModel->isFollowed($userID, $categoryID);
            $isEmailDisabled =
                Gdn::config("Garden.Email.Disabled") || !Gdn::session()->checkPermission("Garden.Email.View");

            $emailDigestEnabled = !Gdn::config("Garden.Email.Disabled") && Gdn::config("Garden.Digest.Enabled");

            $notificationPreferences = $categoryModel->getPreferences($userID)[$categoryID]["preferences"] ?? [];
            $cleanNotificationPreferences = $categoryModel->normalizePreferencesOutput($notificationPreferences);

            $output = TwigStaticRenderer::renderReactModule("CategoryFollowDropDown", [
                "userID" => $userID,
                "categoryID" => $categoryID,
                "categoryName" => $category["Name"],
                "isFollowed" => $following,
                "notificationPreferences" => $cleanNotificationPreferences,
                "emailDigestEnabled" => $emailDigestEnabled,
                
            ]);
        }
        return $output;
    }
endif;
