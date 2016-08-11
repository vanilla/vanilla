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
    <a href="<?php echo url('/dashboard/settings/gettingstarted'); ?>"><?php echo t('Getting Started'); ?></a>
    <a href="<?php echo url('/dashboard/settings/tutorials'); ?>" class="active"><?php echo t('Help &amp; Tutorials'); ?></a>
</div>
<div class="hero">
    <div class="hero-content">
        <div class="hero-title"><?php echo $CurrentTutorial['Name']; ?></div>
        <div class="hero-body"><?php echo $CurrentTutorial['Description']; ?></div>
    </div>
    <div class="hero-media-wrapper">
        <iframe wmode="transparent"
                src="//player.vimeo.com/video/<?php echo $CurrentTutorial['VideoID']; ?>?title=0&byline=0&portrait=0&color=D0D9E0"
                width="700" height="394?wmode=transparent" frameborder="0"></iframe>
    </div>
</div>
<div class="video-sections">
    <div class="video-section">
        <div class="video-section-heading"><?php echo t('Other Tutorials'); ?></div>
        <div class="videos label-selector">
        <?php
        foreach ($Tutorials as $Tutorial) {
        $current = ($CurrentTutorialCode == $Tutorial['Code']) ? 'current' : '';
        echo '<div class="video label-selector-item '.$current.'">';
        echo '<div class="image-wrap">';
        echo '<img src="'.$Tutorial['Thumbnail'].'" alt="'.$Tutorial['Name'].'" class = "video-img label-selector-image" />'; ?>
            <a class="overlay" href="<?php echo url('/settings/tutorials/'.$Tutorial['Code']); ?>">
                <div class="buttons">
                    <div class="icon-wrapper"><?php echo dashboardSymbol('play')?></div>
                </div>
                <div class="selected"></div>
            </a>
            <?php
            echo '</div>';
            echo wrap($Tutorial['Name'], 'div', ['class' => 'video-title title']);
            echo '</div>';
        } ?>
        </div>
    </div>
</div>
