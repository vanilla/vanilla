<?php

use Vanilla\Forum\Modules\FoundationDiscussionsShim;
use Vanilla\Forum\Modules\FoundationShimOptions;
use Vanilla\Theme\BoxThemeShim;
use Vanilla\Utility\HtmlUtils;

require_once $this->fetchViewLocation('helper_functions');

/** @var DiscussionsModule $module */
$module = $this;

$boxClasses = $module->isFullView() ? '' : 'Box BoxDiscussions';
if (!isset($this->Prefix)) {
    $module->Prefix = 'Discussion';
}
$discussions = $module->data('Discussions')->result();
$hasViewAll = $module->data('Discussions')->numRows() >= $module->Limit;
$viewAllUrl = url('/discussions', true);
$listClasses = HtmlUtils::classNames("DataList", $module->isFullView() ? 'Discussions pageBox' : 'PanelInfo PanelDiscussions');
$discussions = $module->data('Discussions')->result();
$title = t($module->getTitle() ?? 'Recent Discussions');

if (FoundationDiscussionsShim::isEnabled()) {
    $options = FoundationShimOptions::create();
    if ($module->showTitle) {
        $options->setTitle($title);
    }
    if ($hasViewAll) {
        $options->setViewAllUrl($viewAllUrl);
    }
    FoundationDiscussionsShim::printLegacyShim($discussions, $options);
} else {
    echo "<div class='$boxClasses'>";
        if ($module->showTitle) {
            BoxThemeShim::startHeading('isSmall');
            echo panelHeading($title);
            BoxThemeShim::endHeading();
        }

        echo "<ul class='$listClasses'>";
            foreach ($discussions as $discussion) {
                if ($module->isFullView()) {
                    writeDiscussion($discussion, \Gdn::controller(), \Gdn::session());
                } else {
                    writeModuleDiscussion($discussion, $module->Prefix, $module->getShowPhotos());
                }
            }
            if ($module->data('Discussions')->numRows() >= $module->Limit) {
                echo "<li class='ShowAll'>";
                    echo anchor(t('More…'), $viewAllUrl, '', ['aria-label' => strtolower(sprintf(t('%s discussions'), t('View all')))]);
                echo "</li>";
            }
        echo "</ul>";
    echo "</div>";
}
