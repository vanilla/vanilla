<?php if (!defined('APPLICATION')) exit(); ?>

<ul id="StatsOverview">
    <li id="PageViews">
        <div>
            <?php echo t('Page Views'); ?>
            <span class="StatsValue">-</span>
        </div>
    </li>
    <li id="NewUsers">
        <div>
            <?php echo t('Users'); ?>
            <span class="StatsValue">-</span>
        </div>
    </li>
    <li id="NewDiscussions">
        <div>
            <?php echo t('Discussions'); ?>
            <span class="StatsValue">-</span>
        </div>
    </li>
    <li id="NewComments">
        <div>
            <?php echo t('Comments'); ?>
            <span class="StatsValue">-</span>
        </div>
    </li>
</ul>

<div class="Column Column1 ReleasesColumn">
    <h1><?php echo t('Updates'); ?></h1>
    <div class="List"></div>
</div>
<div class="Column Column2 NewsColumn">
    <h1><?php echo t('Recent News'); ?></h1>
    <div class="List"></div>
</div>
