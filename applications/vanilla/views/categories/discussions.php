<?php
if (!defined('APPLICATION')) exit();

use Vanilla\Theme\BoxThemeShim;
use Vanilla\Utility\HtmlUtils;
BoxThemeShim::startHeading();
echo '<h1 class="H HomepageTitle">'.$this->data('Title').'</h1>';
BoxThemeShim::endHeading();
if ($this->data('EnableFollowingFilter')) {
    echo '<div class="PageControls Top">'.categoryFilters().'</div>';
}
$ViewLocation = $this->fetchViewLocation('discussions', 'discussions');
$dataDriven = \Gdn::themeFeatures()->useDataDrivenTheme();
?>
<div class="Categories pageBox">
    <?php if ($this->CategoryData->numRows() > 0): ?>
        <?php foreach ($this->CategoryData->result() as $Category) :
            if ($Category->CategoryID <= 0)
                continue;

                $this->Category = $Category;
                $this->DiscussionData = $this->CategoryDiscussionData[$Category->CategoryID] ?? null;
                $this->AnnounceData = $this->CategoryAnnounceData[$Category->CategoryID] ?? null;
                $options = getOptions($Category);

            ?>

        <?php if (!BoxThemeShim::isActive()) { ?>
            <div class="CategoryBox Category-<?php echo $Category->UrlCode; ?>">
        <?php } ?>

                <?php
                    if (!$dataDriven) {
                        if ($this->Category->DisplayAs === "Discussions") {
                            echo $options;
                        }
                    } else {
                        echo "<div class='CategoryBox-Head pageHeadingBox'>";
                    }
                ?>
                <?php if ($this->Category->DisplayAs === "Heading") : ?>
                    <h2 class="H">
                        <?php
                            echo htmlspecialchars($Category->Name);
                            Gdn::controller()->EventArguments['Category'] = $Category;
                            Gdn::controller()->fireEvent('AfterCategoryTitle');
                        ?>
                    </h2>
                <?php else: ?>
                    <h2 class="H">
                        <?php
                        $accessibleLabel = HtmlUtils::accessibleLabel('Category: "%s"', [$Category->Name]);
                        echo anchor(htmlspecialchars($Category->Name), categoryUrl($Category), ["aria-label" => $accessibleLabel]);
                        Gdn::controller()->EventArguments['Category'] = $Category;
                        Gdn::controller()->fireEvent('AfterCategoryTitle');
                        ?>
                    </h2>
                <?php endif; ?>
                <?php
                    if ($dataDriven) {
                        if ($this->Category->DisplayAs === "Discussions") {
                            echo $options;
                        }
                        echo "</div>";
                    }
                ?>
                <?php if (
                    (!empty($this->DiscussionData) && $this->DiscussionData->numRows() > 0) ||
                    (!empty($this->AnnounceData) && $this->AnnounceData->numRows() > 0)
                ) : ?>
                    <ul class="DataList Discussions pageBox">
                        <?php include($this->fetchViewLocation('discussions', 'discussions')); ?>
                    </ul>

                    <?php if ($this->DiscussionData->numRows() == $this->DiscussionsPerCategory) : ?>
                        <div class="MorePager">
                            <?php
                            $moreLabel = t('More Discussions');
                            $accessibleLabel= HtmlUtils::accessibleLabel('%s for category: "%s"', [$moreLabel, is_array($Category) ? $Category["Name"] : $Category->Name]);
                            echo anchor($moreLabel, '/categories/'.$Category->UrlCode, ["aria-label" => $accessibleLabel]); ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <?php if ($this->Category->DisplayAs === "Discussions") {
                        echo '<div class="Empty">'.t('No discussions were found.').'</div>';
                    } ?>
                <?php endif; ?>
            <?php if (!BoxThemeShim::isActive()) { ?>
            </div>
            <?php } ?>
        <?php endforeach; ?>
    <?php else: ?>
        <?php BoxThemeShim::startBox(); ?>
        <div class="Empty"><?php echo t('No categories were found.'); ?></div>
        <?php BoxThemeShim::endBox(); ?>
    <?php endif; ?>
</div>
