<?php if (!defined('APPLICATION')) exit();
$AllowEmbed = c('Garden.Embed.Allow');
?>
<style type="text/css">
    .Info form ul {
        margin: 0;
        padding: 0;
    }

    .Info form li {
        list-style: none;
    }

    form em {
        font-size: 11px;
        color: #999;
    }

    p.WarningMessage {
        padding: 6px;
        margin-bottom: 20px;
    }
</style>
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
<div class="Info">
    <h2>Trusted Domains</h2>

    <p>You can optionally specify a white-list of trusted domains (ie.
        yourdomain.com) that are allowed to embed elements of your community
        (forum, comments, or modules).</p>

    <p>
        <small>
            <strong>Notes:</strong>
            Specify one domain per line, without protocol (ie. yourdomain.com).
            <br/>The domain will include all subdomains (ie. yourdomain.com will
            also allow blog.yourdomain.com, news.yourdomain.com, etc).
            <br/>Leaving this input blank will mean that you allow embedding on any site, anywhere.
        </small>
    </p>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    echo wrap($this->Form->textBox('Garden.TrustedDomains', array('MultiLine' => TRUE)), 'p');
    ?>

    <h2>Forum Embed Settings</h2>

    <p>The url where the forum is embedded:</p>

    <p><?php echo $this->Form->textBox('Garden.Embed.RemoteUrl'); ?> <em>Example: http://yourdomain.com/forum/</em></p>

    <p><?php echo $this->Form->CheckBox('Garden.Embed.ForceForum', "Force the forum to only be accessible through this url"); ?></p>

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
</div>
