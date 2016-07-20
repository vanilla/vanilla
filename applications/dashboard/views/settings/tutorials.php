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
<div class="Tutorials">
    <div class="Video">
        <iframe wmode="transparent"
                src="//player.vimeo.com/video/<?php echo $CurrentTutorial['VideoID']; ?>?title=0&byline=0&portrait=0&color=D0D9E0"
                width="700" height="394?wmode=transparent" frameborder="0"></iframe>
    </div>
    <div class="VideoInfo">
        <?php
        echo wrap($CurrentTutorial['Name'], 'strong');
        echo wrap($CurrentTutorial['Description'], 'em');
        ?>
    </div>
    <div class="Videos">
        <h2><?php echo t('Other Tutorials'); ?></h2>
        <?php
        foreach ($Tutorials as $Tutorial) {
            echo anchor(
                '<img src="'.$Tutorial['Thumbnail'].'" alt="'.$Tutorial['Name'].'" />'
                .Wrap($Tutorial['Name'], 'span'),
                'settings/tutorials/'.$Tutorial['Code'],
                ($CurrentTutorialCode == $Tutorial['Code'] ? 'Current' : '')
            );
        }
        ?>
    </div>
</div>
