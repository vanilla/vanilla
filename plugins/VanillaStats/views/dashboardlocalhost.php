<?php if (!defined('APPLICATION')) exit(); ?>
<?php Gdn_Theme::assetBegin('Help'); ?>
<div class="Help Aside">
    <?php
    echo '<h2>', t('Need More Help?'), '</h2>';
    echo '<ul>';
    echo '<li>', anchor(t('Using Vanilla Stats on localhost'), 'http://docs.vanillaforums.com/addons/statistics/'), '</li>';
    echo '</ul>';
    ?>
</div>
<?php Gdn_Theme::assetEnd(); ?>
<div class="Messages Errors">
    <ul>
        <li><?php echo t('Vanilla statistics are disabled on localhost.'); ?></li>
    </ul>
</div>
