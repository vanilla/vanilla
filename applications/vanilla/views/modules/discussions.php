<?php use Vanilla\Theme\BoxThemeShim;
use Vanilla\Utility\HtmlUtils;

if (!defined('APPLICATION')) exit();
require_once $this->fetchViewLocation('helper_functions');

/** @var DiscussionsModule $module */
$module = $this;

$boxClasses = $module->isFullView() ? '' : 'Box BoxDiscussions';
if (!isset($this->Prefix)) {
    $module->Prefix = 'Discussion';
}
?>
<div class="<?php echo $boxClasses ?>">
<?php
if ($module->showTitle) {
    BoxThemeShim::startHeading('isSmall');
    echo panelHeading(t($module->getTitle() ?? 'Recent Discussions'));
    BoxThemeShim::endHeading();
}
$listClasses = HtmlUtils::classNames("DataList", $module->isFullView() ? 'Discussions pageBox' : 'PanelInfo PanelDiscussions');
?>
    <ul class="<?php echo $listClasses ?>">
        <?php
        foreach ($module->data('Discussions')->result() as $discussion) {
            if ($module->isFullView()) {
                writeDiscussion($discussion, \Gdn::controller(), \Gdn::session());
            } else {
                writeModuleDiscussion($discussion, $module->Prefix, $module->getShowPhotos());
            }
        }
        if ($module->data('Discussions')->numRows() >= $module->Limit) {
            ?>
            <li class="ShowAll"><?php echo anchor(t('More…'), 'discussions', '', ['aria-label' => strtolower(sprintf(t('%s discussions'), t('View all')))]); ?></li>
        <?php } ?>
    </ul>
</div>
