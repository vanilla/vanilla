<?php if (!defined('APPLICATION')) exit(); ?>
<div id="RecentActivity" class="Box">
    <h4 aria-level="2"><?php echo val('ActivityModuleTitle', $this, t('Recent Activity')); ?></h4>
    <ul class="PanelInfo">
        <?php
        $Data = $this->ActivityData;
        foreach ($Data->result() as $Activity) {
            $activityHeadlineText = Gdn::formatService()->renderPlainText($Activity['Headline'], Vanilla\Formatting\Formats\HtmlFormat::FORMAT_KEY);
            $PhotoAnchor = anchor(
                img($Activity['Photo'], ['class' => 'ProfilePhotoSmall', 'alt' => $activityHeadlineText]),
                $Activity['PhotoUrl'], 'Photo');

            echo '<li class="Activity '.$Activity['ActivityType'].'">';
            echo $PhotoAnchor.' '.$Activity['Headline'];
            echo '</li>';
        }

        if ($Data->numRows() >= $this->Limit) {
            ?>
            <li class="ShowAll"><?php echo anchor(t('More…'), '/activity', '', ['aria-label' => strtolower(sprintf(t('%s activities'), t('View All')))]); ?></li>
        <?php
        }
        ?>
    </ul>
</div>
