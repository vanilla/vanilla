<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Social Integration'); ?></h1>
<?php Gdn_Theme::assetBegin('Help')?>
<div>
    <h2><?php echo t("What's This?"); ?></h2>

    <p>
        <?php
        echo t('Here is a list of all your social addons.',
            "Here's a list of all your social addons. You can enable, disable, and configure them from this page.");
        ?>
    </p>
</div>
<?php Gdn_Theme::assetEnd() ?>
<?php include('connection_functions.php'); ?>

<ul class="DataList DataList-Connections"><?php

    foreach ($this->data('Connections') as $Key => $Row) {
        WriteConnection($Row);
    }

    ?></ul>
