<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Social Integration'); ?></h1>

<div class="PageInfo">
    <h2><?php echo t("What's This?"); ?></h2>

    <p>
        <?php
        echo t('Here is a list of all your social addons.',
            "Here's a list of all your social addons. You can enable, disable, and configure them from this page.");
        ?>
    </p>
</div>

<?php include('connection_functions.php'); ?>

<style>
    .DataList-Connections {
        padding: 0px 20px;
    }

    .DataList-Connections .Item {
        margin: 15px 0px;
        min-width: 700px;
    }

    .Conneciton-Header * {
        line-height: 48px;
        position: relative;
    }

    .Connection-Info {
        display: inline-block;
        padding-right: 150px;
        max-width: 580px;
    }

    .Connection-Name {
        font-size: 20px;
        line-height: 1;
        display: block;
    }

    .Connection-Name .Addendums {
        font-size: 12px;
        color: #b6b6b6;
    }

    .Connection-Name .RequiresRegistration {

    }

    .Connection-Name .NotConfigured {
        color: red;
    }

    .Connection-Description {
        clear: left;
        font-size: 12px;
        line-height: 1.1;
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

    .Connection-Buttons {
        position: absolute;
        right: 0;
        bottom: 5px;
        padding: 5px;
    }

    .Connection-Buttons * {
        vertical-align: middle;
        line-height: 1;
    }

    .Connection-Buttons a:last-child {
        margin-right: 0px;
    }

    .Connection-Buttons a .Sprite {
        vertical-align: text-top;
    }

    .ActivateSlider {
        width: 80px;
        border-radius: 2px;
    }

</style>

<ul class="DataList DataList-Connections"><?php

    foreach ($this->data('Connections') as $Key => $Row) {
        WriteConnection($Row);
    }

    ?></ul>
