<?php
if (!defined('APPLICATION')) {
    exit();
}

use Vanilla\Forum\Modules\FoundationDiscussionsShim;
use Vanilla\Forum\Modules\FoundationShimOptions;
use Vanilla\Theme\BoxThemeShim;
use Vanilla\Utility\HtmlUtils;

BoxThemeShim::startHeading();
echo '<h1 class="H HomepageTitle">' . $this->data('Title') . '</h1>';
BoxThemeShim::endHeading();
if ($this->data('EnableFollowingFilter')) {
    echo '<div class="PageControls Top">' . categoryFilters() . '</div>';
}
$ViewLocation = $this->fetchViewLocation('discussions', 'discussions');

?>
<div class="Categories pageBox">
    <?php if ($this->CategoryData->numRows() > 0): ?>
        <?php foreach ($this->CategoryData->result() as $Category) :
            if ($Category->CategoryID <= 0) {
                continue;
            }
            $this->Category = $Category;
            $this->DiscussionData = $this->CategoryDiscussionData[$Category->CategoryID] ?? null;
            $this->AnnounceData = $this->CategoryAnnounceData[$Category->CategoryID] ?? null;

            $hasData = ($this->DiscussionData && $this->DiscussionData->numRows() > 0)
                || ($this->AnnounceData && $this->AnnounceData->numRows() > 0);
            $categoryArr = (array)$Category;
            $moreLabel = t('More Discussions');
            $moreUrl = categoryUrl($categoryArr, 1, true);
            $isPureHeading = $this->Category->DisplayAs === "Heading";

            if (FoundationDiscussionsShim::isEnabled() && !$isPureHeading) {
                $announceData = $this->AnnounceData ? $this->AnnounceData->resultArray() : [];
                $regularData = $this->DiscussionData ? $this->DiscussionData->resultArray() : [];
                $legacyData = array_merge($announceData, $regularData);

                echo FoundationDiscussionsShim::renderLegacyShim(
                    $legacyData,
                    FoundationShimOptions::create()
                        ->setTitle($categoryArr['Name'])
                        ->setViewAllUrl($moreUrl)
                        ->setDescription($categoryArr['Description'])
                );
                // Early bailout from the shim.
                continue;
            }
            ?>

            <?php BoxThemeShim::inactiveHtml("<div class='CategoryBox Category-{$Category->UrlCode}'>"); ?>
            <?php
            $options = $isPureHeading ? "" : getOptions($Category);
            $accessibleLabel = HtmlUtils::accessibleLabel('Category: "%s"', [$Category->Name]);

            $headingContent = "";
            if (!BoxThemeShim::isActive()) {
                $headingContent .= $options;
            }

            $headingContent .= "<h2>";
            $headingContent .= anchor(htmlspecialchars($Category->Name), categoryUrl($Category), ["aria-label" => $accessibleLabel]);
            $headingContent .= "</h2>";
            if (BoxThemeShim::isActive()) {
                $headingContent .= $options;
            }

            echo "<div class='CategoryBox-Head pageHeadingBox'>";
            echo $headingContent;
            Gdn::controller()->EventArguments['Category'] = $Category;
            Gdn::controller()->fireEvent('AfterCategoryTitle');
            echo "</div>";

            ?>

            <?php if ($hasData) {
            echo '<ul class="DataList Discussions pageBox">';
            include($this->fetchViewLocation('discussions', 'discussions'));
            echo "</ul>";
            ?>

            <?php if ($this->DiscussionData->numRows() == $this->DiscussionsPerCategory) : ?>
                <div class="MorePager">
                    <?php
                    $accessibleLabel = HtmlUtils::accessibleLabel('%s for category: "%s"', [$moreLabel, $categoryArr['Name']]);
                    echo anchor($moreLabel, $moreUrl, ["aria-label" => $accessibleLabel]); ?>
                </div>
            <?php endif; ?>

        <?php } else { ?>
            <?php if ($this->Category->DisplayAs === "Discussions") {
                echo '<div class="Empty">' . t('No discussions were found.') . '</div>';
            } ?>
        <?php } ?>
            <?php BoxThemeShim::inactiveHtml('</div>'); ?>
        <?php endforeach; ?>
    <?php else: ?>
        <?php BoxThemeShim::startBox(); ?>
        <div class="Empty"><?php echo t('No categories were found.'); ?></div>
        <?php BoxThemeShim::endBox(); ?>
    <?php endif; ?>
</div>
