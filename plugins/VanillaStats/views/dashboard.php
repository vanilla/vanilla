<?php if (!defined('APPLICATION')) exit(); ?>

<div id="StatsToolbar">
    <ul id="StatsOverview">
        <li class="StatsType" id="StatsNewUsers">
            <div>
                <?php echo t('Users'); ?>
                <span class="StatsValue">-</span>
            </div>
        </li>
        <li class="StatsType" id="StatsNewDiscussions">
            <div>
                <?php echo t('Discussions'); ?>
                <span class="StatsValue">-</span>
            </div>
        </li>
        <li class="StatsType" id="StatsPageViews">
            <div>
                <?php echo t('Page Views'); ?>
                <span class="StatsValue">-</span>
            </div>
        </li>
        <li class="StatsType" id="StatsNewComments">
            <div>
                <?php echo t('Comments'); ?>
                <span class="StatsValue">-</span>
            </div>
        </li>
    </ul>

    <div id="StatsSlotSelector">
        <input disabled="disabled" id="StatsSlotDay" type="button" value="<?php echo t('Day');?>" />
        <input disabled="disabled" id="StatsSlotMonth" type="button" value="<?php echo t('Month'); ?>" />
    </div>

    <div id="StatsNavigation">
        <p id="StatsCurrentTimeframe"></p>
        <input disabled="disabled" id="StatsNavPrev" type="button" value="<" />
        <input disabled="disabled" id="StatsNavNext" type="button" value=">" />
        <input disabled="disabled" id="StatsNavToday" type="button" value="<?php echo t('Today'); ?>"/>
    </div>
</div>

<div id="StatsChart"></div>

<div class="Column Column1 ReleasesColumn">
    <h1><?php echo t('Updates'); ?></h1>
    <div class="List"></div>
</div>
<div class="Column Column2 NewsColumn">
    <h1><?php echo t('Recent News'); ?></h1>
    <div class="List"></div>
</div>
