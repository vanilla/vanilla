<?php
    if (!defined('APPLICATION')) exit();
    use Vanilla\Theme\BoxThemeShim;

    $dataDriven = \Gdn::themeFeatures()->useDataDrivenTheme();
?>

<div class="Box Moderators">
    <?php BoxThemeShim::startHeading(); ?>
    <?php echo panelHeading(t('Moderators')); ?>
    <?php BoxThemeShim::endHeading(); ?>

    <ul class="PanelInfo <?php BoxThemeShim::activeHtml("pageBox"); ?>">
        <?php
        $moderators = $this->data('Moderators', []);
        foreach ($moderators as $user) {
            $photo = userPhoto($user, 'Small');
            $anchor = userAnchor($user);

            echo "<li>{$photo} {$anchor}</li>";
        }
        ?>
    </ul>
</div>
