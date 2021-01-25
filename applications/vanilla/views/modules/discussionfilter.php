<?php
if (!defined('APPLICATION')) exit();

use Vanilla\Utility\HtmlUtils;

if (\Gdn::themeFeatures()->useNewQuickLinks()) {
    echo \Gdn_Theme::module(\Vanilla\Theme\VariableProviders\QuickLinksModule::class);
    return;
}

$Controller = Gdn::controller();
$Session = Gdn::session();
$Title = property_exists($Controller, 'Category') ? val('Name', $Controller->Category, '') : '';
if ($Title == '')
    $Title = t('All Discussions');

$Bookmarked = t('My Bookmarks');
$MyDiscussions = t('My Discussions');
$MyDrafts = t('My Drafts');
$CountBookmarks = 0;
$CountDiscussions = 0;
$CountDrafts = 0;

if ($Session->isValid()) {
    $CountBookmarks = $Session->User->CountBookmarks ?? 0;
    $CountDiscussions = $Session->User->CountDiscussions ?? 0;
    $CountDrafts = $Session->User->CountDrafts ?? 0;
}

if (!function_exists('FilterCountString')) {
    function filterCountString($count, $url = '') {
        $count = countString($count, $url);
        return $count != '' ? '<span class="Aside">'.$count.'</span>' : '';
    }
}
if (c('Vanilla.Discussions.ShowCounts', true)) {
    $Bookmarked .= filterCountString($CountBookmarks, '/discussions/UserBookmarkCount');
    $MyDiscussions .= filterCountString($CountDiscussions);
    $MyDrafts .= filterCountString($CountDrafts);
}
$titleClasses = HtmlUtils::classNames(
    !Gdn::themeFeatures()->useDataDrivenTheme() && "sr-only",
    "BoxFilter-HeadingWrap"
);
$titleID = "BoxFilterTitle";
?>
<div class="BoxFilter BoxDiscussionFilter" role="navigation" aria-labelledby="<?php echo $titleID ?>">
    <span class="<?php echo $titleClasses ?>">
        <h2 id="<?php echo $titleID ?>" class="BoxFilter-Heading">
            <?php echo t('Quick Links'); ?>
        </h2>
    </span>
    <ul class="FilterMenu">
        <?php
        $Controller->fireEvent('BeforeDiscussionFilters');
        //      if (c('Vanilla.Categories.ShowTabs')) {
        if (c('Vanilla.Categories.Use')) {
            $CssClass = 'AllCategories';
            if (strtolower($Controller->ControllerName) == 'categoriescontroller' && in_array(strtolower($Controller->RequestMethod), ['index', 'all'])) {
                $CssClass .= ' Active';
            }

            echo '<li class="'.$CssClass.'">'.anchor(sprite('SpAllCategories').' '.t('All Categories', 'Categories'), '/categories').'</li> ';
        }
        ?>
        <li class="Discussions<?php echo strtolower($Controller->ControllerName) == 'discussionscontroller' && strtolower($Controller->RequestMethod) == 'index' ? ' Active' : ''; ?>"><?php echo Gdn_Theme::link('forumroot', sprite('SpDiscussions').' '.t('Recent Discussions')); ?></li>
        <?php echo Gdn_Theme::link('activity', sprite('SpActivity').' '.t('Activity'), '<li class="Activities"><a href="%url" class="%class">%text</a></li>'); ?>
        <?php if ($CountBookmarks > 0 || $Controller->RequestMethod == 'bookmarked') { ?>
            <li class="MyBookmarks<?php echo $Controller->RequestMethod == 'bookmarked' ? ' Active' : ''; ?>"><?php echo anchor(sprite('SpBookmarks').' '.$Bookmarked, '/discussions/bookmarked'); ?></li>
        <?php
        }
        if (($CountDiscussions > 0 || $Controller->RequestMethod == 'mine') && c('Vanilla.Discussions.ShowMineTab', true)) {
            ?>
            <li class="MyDiscussions<?php echo $Controller->RequestMethod == 'mine' ? ' Active' : ''; ?>"><?php echo anchor(sprite('SpMyDiscussions').' '.$MyDiscussions, '/discussions/mine'); ?></li>
        <?php
        }
        if ($CountDrafts > 0 || $Controller->ControllerName == 'draftscontroller') {
            ?>
            <li class="MyDrafts<?php echo $Controller->ControllerName == 'draftscontroller' ? ' Active' : ''; ?>"><?php echo anchor(sprite('SpMyDrafts').' '.$MyDrafts, '/drafts'); ?></li>
        <?php
        }
        $Controller->fireEvent('AfterDiscussionFilters');
        ?>
    </ul>
</div>
