<?php if (!defined('APPLICATION')) exit(); ?>

<div id="StatsToolbar" class="toolbar toolbar-stats">
    <ul id="StatsOverview" class="toolbar-stats-navigation nav nav-stats">
        <li class="nav-stats-item nav-stats-link nav-stats-users" id="StatsUsers">
            <div class="nav-stats-value StatsValue">-</div>
            <div class="nav-stats-title"><?php echo t('Users'); ?></div>
            <div class="Sparkline"></div>
        </li>
        <li class="nav-stats-item nav-stats-link nav-stats-discussions" id="StatsDiscussions">
            <div class="nav-stats-value StatsValue">-</div>
            <div class="nav-stats-title"><?php echo t('Discussions'); ?></div>
            <div class="Sparkline"></div>
        </li>
        <li class="nav-stats-item nav-stats-link nav-stats-pageviews" id="StatsPageViews">
            <div class="nav-stats-value StatsValue">-</div>
            <div class="nav-stats-title"><?php echo t('Page Views'); ?></div>
            <div class="Sparkline"></div>
        </li>
        <li class="nav-stats-item nav-stats-link nav-stats-comments" id="StatsComments">
            <div class="nav-stats-value StatsValue">-</div>
            <div class="nav-stats-title"><?php echo t('Comments'); ?></div>
            <div class="Sparkline"></div>
        </li>
    </ul>
    <div id="StatsSlotSelector" class="btn-group toolbar-stats-slot">
        <button disabled="disabled" id="StatsSlotDay" class="btn btn-secondary"><?php echo t('Day');?></button>
        <button disabled="disabled" id="StatsSlotMonth" class="btn btn-secondary"><?php echo t('Month'); ?></button>
    </div>
    <div id="StatsNavigation" class="toolbar-stats-daterange">
        <div id="StatsCurrentTimeframe" class="toolbar-stats-daterange-preview"></div>
        <div class="btn-group pager-wrap">
            <button disabled="disabled" id="StatsNavPrev" class="btn btn-secondary btn-icon-border"><?php echo dashboardSymbol('chevron-left'); ?></button>
            <button disabled="disabled" id="StatsNavNext" class="btn btn-secondary btn-icon-border"><?php echo dashboardSymbol('chevron-right'); ?></button>
            <button disabled="disabled" id="StatsNavToday" class="btn btn-secondary"><?php echo t('Today'); ?></button>
        </div>
    </div>
</div>

<div id="StatsChart"></div>

<div class="dashboard-widgets-summaries dashboard-widgets js-dashboard-widgets-summaries">
    <div class="Loading"></div>
</div>

<div class="dashboard-widgets">
    <div class="Column Column1 ReleasesColumn">
        <div class="dashboard-widget-title"><?php echo t('Updates'); ?></div>
        <div class="List"></div>
    </div>
    <div class="Column Column2 NewsColumn">
        <div class="dashboard-widget-title"><?php echo t('Recent News'); ?></div>
        <div class="List"></div>
    </div>
</div>
