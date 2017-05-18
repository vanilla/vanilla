<?php if (!defined('APPLICATION')) exit();
require_once $this->fetchViewLocation('helper_functions');
$Tutorials = getTutorials();

// Figure out the current video
$CurrentTutorialCode = $this->data('CurrentTutorial');
$Tutorials = array_column($Tutorials, null, 'Code');
if (isset($Tutorials[$CurrentTutorialCode])) {
    $CurrentTutorial = $Tutorials[$CurrentTutorialCode];
} else {
    $CurrentTutorial = reset($Tutorials);
    $CurrentTutorialCode = key($Tutorials);
}

?>
<div class="header-menu">
    <a class="header-menu-item" href="<?php echo url('/dashboard/settings/gettingstarted'); ?>"><?php echo t('Getting Started'); ?></a>
    <a class="header-menu-item active" role="heading" aria-level="1" href="<?php echo url('/dashboard/settings/tutorials'); ?>"><?php echo t('Help &amp; Tutorials'); ?></a>
</div>
<?php
$currentTutorialIframe = '<iframe wmode="transparent" src="//player.vimeo.com/video/'
    .$CurrentTutorial['VideoID']
    .'?title=0&byline=0&portrait=0&color=D0D9E0" width="700" height="394?wmode=transparent" frameborder="0"></iframe>';
echo hero($CurrentTutorial['Name'], $CurrentTutorial['Description'], [], $currentTutorialIframe);
?>
<div class="video-sections">
    <div class="video-section">
        <div class="video-section-heading"><?php echo t('Other Tutorials'); ?></div>
        <div class="videos label-selector">
        <?php
        foreach ($Tutorials as $Tutorial) {
        $current = ($CurrentTutorialCode == $Tutorial['Code']) ? 'active' : '';
        echo '<div class="video label-selector-item '.$current.'">';
        echo '<div class="image-wrap">';
        echo '<img src="'.$Tutorial['Thumbnail'].'" alt="'.$Tutorial['Name'].'" class = "video-img label-selector-image" />'; ?>
            <a class="overlay" href="<?php echo url('/settings/tutorials/'.$Tutorial['Code']); ?>">
                <div class="icon-wrapper"><?php echo dashboardSymbol('play')?></div>
            </a>
            <?php
            echo '</div>';
            echo wrap($Tutorial['Name'], 'div', ['class' => 'video-title title']);
            echo '</div>';
        } ?>
        </div>
    </div>
</div>
