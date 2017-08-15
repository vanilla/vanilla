<?php if (!defined('APPLICATION')) exit(); ?>

<style>
    .Connection-Name {
        font-size: 28px;
    }

    .IconWrap {
        margin-right: 10px;
    }

    .IconWrap img {
        height: 48px;
        width: 48px;
        vertical-align: bottom;
        border-radius: 3px;
    }

    .DataList-Connections .Connection-Header {
        overflow: hidden;
        position: relative;
    }

    .DataList-Connections .ProfilePhoto {
        vertical-align: text-bottom;
    }

    .Connection-Connect {
        position: absolute;
        right: 0;
        bottom: 0;
        padding: 5px;
    }

    .Gloss.Connected {
        position: absolute;
        bottom: 5px;
        left: 250px;
    }
</style>

<h1 class="H"><?php echo $this->data('Title'); ?></h1>

<div class="Hero">
    <h3><?php echo t("What's This?"); ?></h3>

    <p>
        <?php
        echo Gdn_Format::markdown(t('Connect your profile to social networks.', "Connect your profile to social networks to be notified of activity here and share your activity with your friends and followers."));
        ?>
    </p>
</div>

<ul class="DataList DataList-Connections">
    <?php
    foreach ($this->data('Connections') as $Key => $Row) {
        writeConnection($Row);
    }
    ?>
</ul>
