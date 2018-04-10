<?php if (!defined('APPLICATION')) exit(); ?>
<?php
helpAsset(t('Need More Help?'), anchor(t("Embedding Documentation"), 'http://docs.vanillaforums.com/features/embedding/'));
echo heading(t('Embedding'));
?>
<div class="row form-group">
    <div class="label-wrap-wide">
        <div class="label"><?php echo t('Embed My Forum'); ?></div>
        <div class="label-description"><?php echo t('If you want to embed your forum or use Vanilla\'s comments in your blog then you need to enable embedding. If you aren\'t using embedding then we recommend leaving this setting off.'); ?></div>
    </div>
    <div class="input-wrap-right">
    <span id="plaintext-toggle">
        <?php
        if (c('Garden.Embed.Allow', false)) {
            echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', 'embed/forum/disable/'.Gdn::session()->transientKey()), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
        } else {
            echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', 'embed/forum/enable/'.Gdn::session()->transientKey()), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
        }
        ?>
    </span>
    </div>
</div>
<?php
$nav = new NavModule();
$nav->addLink(t('Vanilla Plugin for WordPress'), 'embed/wordpress', 'embed.wordpress', '', [], ['icon' => dashboardSymbol('plugin'), 'description' => t('Use Vanilla\'s Wordpress plugin if you want to embed in WordPress site.')]);
$nav->addLink(t('Universal Forum Embed Code'), 'embed/universal', 'embed.universal', '', [], ['icon' => dashboardSymbol('code'), 'description' => t('Use the forum embed code to embed the entire forum in a non-WordPress site.')]);
$nav->addLink(t('Universal Comment Embed Code'), 'embed/comments', 'embed.comments', '', [], ['icon' => dashboardSymbol('code-bubble'), 'description' => t('Use the comment embed code to embed Vanilla comments into a non-WordPress site.')]);
$nav->addLink(t('Embed Settings'), 'embed/settings', 'embed.settings', '', [], ['icon' => dashboardSymbol('settings'), 'description' => t('Use the comment embed code to embed Vanilla comments into a non-WordPress site.')]);
$nav->setView('nav-adventure');

echo $nav;
?>
