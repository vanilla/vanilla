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
    <div id="StatsNavigation" class="toolbar-stats-date-picker flex flex-wrap">
        <div id="StatsFilterDate" class="filter-date">
            <?php echo $this->Form->textBox('dates', ['class' => 'js-daterange date-range form-control']); ?>
        </div>
        <div class="btn-group pager-wrap">
            <button disabled="disabled" id="StatsNavPrev" class="btn btn-icon-border"><?php echo dashboardSymbol('chevron-left'); ?></button>
            <button disabled="disabled" id="StatsNavNext" class="btn btn-icon-border"><?php echo dashboardSymbol('chevron-right'); ?></button>
            <button disabled="disabled" id="StatsNavToday" class="btn btn-secondary"><?php echo t('Today'); ?></button>
        </div>
    </div>
</div>

<div id="StatsChart"></div>

<div class="summaries js-dashboard-widgets-summaries">
    <div class="Loading"></div>
</div>

<div class="summaries">
    <div class="ReleasesColumn">
        <div class="table-summary-title"><?php echo t('Updates'); ?></div>
        <div class="List"></div>
    </div>
    <div class="NewsColumn">
        <div class="table-summary-title"><?php echo t('Recent News'); ?></div>
        <div class="List"></div>
    </div>
</div>
