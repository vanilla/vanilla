<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>
<div class="Info">
    <?php echo t('FlaggedContent', 'The following content has been flagged by users for moderator review.'); ?>
</div>

<?php
// Settings
echo $this->Form->open();
echo $this->Form->errors();
?>
<h3><?php echo t('Flagging Settings'); ?></h3>
<ul>
    <li><?php echo $this->Form->checkBox('Plugins.Flagging.UseDiscussions', t('Create Discussions')); ?></li>
    <li>
        <?php
        echo $this->Form->label('Category to Use', 'Plugins.Flagging.CategoryID');
        echo $this->Form->CategoryDropDown('Plugins.Flagging.CategoryID', array('Value' => c('Plugins.Flagging.CategoryID')));
        ?>
    </li>
</ul>
<?php
echo $this->Form->close('Save');

// Flagged Items list
echo "<h3>".t('Flagged Items')."</h3>\n";
echo '<div class="FlaggedContent">';
$NumFlaggedItems = count($this->FlaggedItems);
if (!$NumFlaggedItems) {
    echo t('FlagQueueEmpty', "There are no items awaiting moderation at this time.");
} else {
    echo sprintf(
        t('Flagging queue counter', '%s in queue.'),
        plural($NumFlaggedItems, '%s post', '%s posts')
    );
    foreach ($this->FlaggedItems as $URL => $FlaggedList) {
        ?>
        <div class="FlaggedItem">
            <?php
            $TitleCell = TRUE;
            ksort($FlaggedList, SORT_STRING);
            $NumComplaintsInThread = sizeof($FlaggedList);
            foreach ($FlaggedList as $FlagIndex => $Flag) {
                if ($TitleCell) {
                    $TitleCell = FALSE;
                    ?>
                    <div class="FlaggedTitleCell">
                        <div
                            class="FlaggedItemURL"><?php echo anchor(url($Flag['ForeignURL'], true), $Flag['ForeignURL']); ?></div>
                        <div class="FlaggedItemInfo">
                            <?php
                            if ($NumComplaintsInThread > 1)
                                $OtherString = t(' and').' '.($NumComplaintsInThread - 1).' '.t(Plural($NumComplaintsInThread - 1, 'other', 'others'));
                            else
                                $OtherString = '';
                            ?>
                            <span><?php echo t('FlaggedBy', "Reported by:"); ?> </span>
                            <span><?php printf(t('<strong>%s</strong>%s on %s'), anchor($Flag['InsertName'], "profile/{$Flag['InsertUserID']}/{$Flag['InsertName']}"), $OtherString, $Flag['DateInserted']); ?></span>
                        </div>
                        <div class="FlaggedItemComment">"<?php echo Gdn_Format::text($Flag['Comment']); ?>"</div>
                        <div class="FlaggedActions">
                            <?php
                            echo $this->Form->button('Dismiss', array(
                                'onclick' => "window.location.href='".Url('plugin/flagging/dismiss/'.$Flag['EncodedURL'], true)."'",
                                'class' => 'SmallButton'
                            ));
                            echo $this->Form->button('Take Action', array(
                                'onclick' => "window.location.href='".Url($Flag['ForeignURL'], true)."'",
                                'class' => 'SmallButton'
                            ));
                            ?>
                        </div>
                    </div>
                    <?php
                    if ($NumComplaintsInThread > 1)
                        echo '<div class="OtherComplaints">'."\n";
                } else {
                    ?>
                    <div class="FlaggedOtherCell">
                        <div
                            class="FlaggedItemInfo"><?php echo t('On').' '.$Flag['DateInserted'].', <strong>'.anchor($Flag['InsertName'], "profile/{$Flag['InsertUserID']}/{$Flag['InsertName']}").'</strong> '.t('said:'); ?></div>
                        <div class="FlaggedItemComment">"<?php echo Gdn_Format::text($Flag['Comment']); ?>"</div>
                    </div>
                <?php
                }
            }
            if ($NumComplaintsInThread > 1)
                echo "</div>\n";
            ?>
        </div>
    <?php
    }
}
?>
</div>
