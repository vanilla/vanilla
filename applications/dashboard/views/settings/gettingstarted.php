<?php if (!defined('APPLICATION')) exit();
require_once $this->fetchViewLocation('helper_functions');

function tutLink($tutorialCode, $writeTitle = TRUE, $thumbnailSize = 'medium', $noGrid = false) {
    $tutorial = getTutorials($tutorialCode);
    if (!$tutorial)
        return '';

    $thumbnail = $thumbnailSize == 'medium' ? $tutorial['Thumbnail'] : $tutorial['LargeThumbnail'];
    $noGrid = ($noGrid) ? 'no-grid' : '';

    ob_start();
    echo '<div class="video label-selector-item '.$noGrid.'">';
    echo '<div class="image-wrap">';
    echo '<img src="'.$thumbnail.'" alt="'.$tutorial['Name'].'" class = "video-img label-selector-image" />'; ?>
    <a class="overlay" href="<?php echo url('/settings/tutorials/'.$tutorial['Code']); ?>">
        <div class="buttons">
            <div class="icon-wrapper"><?php echo dashboardSymbol('play')?></div>
        </div>
        <div class="selected"></div>
    </a>
    <?php
    echo '</div>';
    echo ($writeTitle) ? wrap($tutorial['Name'], 'div', ['class' => 'video-title title']) : '';
    echo '</div>';

    $tutLink = ob_get_contents();
    ob_end_clean();

    return $tutLink;
}
?>

<div class="header-menu">
    <a class="header-menu-item active" role="heading" aria-level="1" href="<?php echo url('/dashboard/settings/gettingstarted'); ?>"><?php echo t('Getting Started'); ?></a>
    <a class="header-menu-item" href="<?php echo url('/dashboard/settings/tutorials'); ?>"><?php echo t('Help &amp; Tutorials'); ?></a>
</div>
<?php
$tagline = wrap(sprintf(t('Getting Started with %s'), ''), 'div', ['class' => 'tagline']);
$logo = wrap('Vanilla Forums', 'div', ['class' => 'vanilla-logo vanilla-logo-black']);
$body = t('Kick-start your community and increase user engagement.');
$body .= ' '.t("Check out these tutorials to get started using Vanilla", "Vanilla is the simplest, most powerful community platform in the world. It's super-duper easy to use. Start with this introductory video and continue with the steps below. Enjoy!");
$button['text'] = t("Full list of video tutorials");
$button['url'] = url('settings/tutorials');
$media = wrap(tutLink('introduction', false, 'large', true), 'div', ['class' => 'label-selector']);

echo hero($tagline.$logo, $body, $button, $media);
?>
<div class="video-sections">
    <div class="video-section">
        <div class="video-section-heading"><?php echo t('The Basics'); ?></div>
        <div class="info"><?php echo t('Learn how to use the basic functionality of your forum.'); ?></div>
        <div class="videos label-selector">
            <?php
            echo tutLink('using-the-forum');
            echo tutLink('private-conversations');
            echo tutLink('user-profiles');
            ?>
        </div>
    </div>
    <div class="video-section">
        <div class="video-section-heading"><?php echo t("Appearance"); ?></div>
        <div class="info"><?php echo t("Learn how to completely change your forum's look and feel: upload your logo, set your homepage, choose a theme and customize it."); ?></div>
        <div class="videos label-selector">
            <?php echo tutLink('appearance'); ?>
        </div>
    </div>
    <div class="video-section">
        <div class="video-section-heading"><?php echo t('Organize'); ?></div>
        <div class="info"><?php echo t('Create & organize discussion categories and manage your users.'); ?></div>
        <div class="videos label-selector">
            <?php
            echo tutLink('user-registration');
            echo tutLink('users');
            echo tutLink('roles-and-permissions');
            echo tutLink('category-management-and-advanced-settings');
            ?>
        </div>
    </div>
</div>
