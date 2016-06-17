<?php if (!defined('APPLICATION')) exit();
$AllowEmbed = c('Garden.Embed.Allow');
?>
<h1><?php echo t('Advanced Embed Settings'); ?></h1>
<div class="Info">
    <?php
    echo 'The following settings apply to all types of embedded forum content: ';
    echo anchor('blog comments', 'embed/comments');
    echo ', and ';
    echo anchor('forum embedding', 'embed/forum');
    echo '.';
    if (!$AllowEmbed) {
        echo wrap('<span style="background: #ff0;">'.t('Embedding is currently DISABLED.').'</span>', 'p');
        echo anchor('Enable Embedding', 'embed/advanced/enable/'.Gdn::session()->TransientKey(), 'SmallButton');
    } else {
    echo wrap('<span style="background: #ff0;">'.t('Embedding is currently ENABLED.').'</span>', 'p');
    echo anchor('Disable Embedding', 'embed/advanced/disable/'.Gdn::session()->TransientKey(), 'SmallButton');
    ?>
</div>
<h1><?php echo t('Settings'); ?></h1>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <h2>Forum Embed Settings</h2>

    <p>The url where the forum is embedded:</p>

    <p><?php echo $this->Form->textBox('Garden.Embed.RemoteUrl'); ?> <em>Example: http://yourdomain.com/forum/</em></p>

    <p><?php echo $this->Form->CheckBox('Garden.Embed.ForceForum', "Force the forum to only be accessible through this url"); ?></p>

    <p><?php echo $this->Form->Checkbox('Garden.Embed.ForceMobile', "Force the forum to only be accessible through this url when viewed on a mobile device."); ?></p>

    <p><?php echo $this->Form->CheckBox('Garden.Embed.ForceDashboard', "Force the dashboard to only be accessible through this url <em>(not recommended)</em>"); ?></p>

    <h2>Sign In Settings</h2>

    <p>
        <small>If you are using SSO you probably need to disable sign in popups.</small>
    </p>
    <p><?php echo $this->Form->CheckBox('Garden.SignIn.Popup', "Use popups for sign in pages."); ?></p>
    <?php
    echo $this->Form->close('Save', '', array('style' => 'margin: 0;'));
    }
    ?>
