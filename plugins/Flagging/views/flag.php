<?php if (!defined('APPLICATION')) exit(); ?>
<?php
$UcContext = htmlspecialchars(ucfirst($this->data('Plugin.Flagging.Data.Context')));
$ElementID = htmlspecialchars($this->data('Plugin.Flagging.Data.ElementID'));
$URL = $this->data('Plugin.Flagging.Data.URL');
$Title = sprintf("Flag this %s", $UcContext);
?>
    <h2><?php echo t($Title); ?></h2>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <div class="Warning">
                <?php echo t('FlagForReview', "You are about to flag this for moderator review. If you're sure you want to do this,
         please enter a brief reason below, then press 'Flag this!'."); ?>
            </div>
            <?php echo t('FlagLinkContent', 'Link to content:').' '.anchor(sprintf(t('FlagLinkFormat', '%1$s #%2$s &ndash; %3$s'), t($UcContext), $ElementID, htmlspecialchars($this->data('Plugin.Flagging.Data.ElementAuthor'))), $URL); ?> 
        </li>
        <li>
            <?php
            echo $this->Form->label('Reason', 'Plugin.Flagging.Reason');
            echo $this->Form->textBox('Plugin.Flagging.Reason', ['MultiLine' => TRUE]);
            ?>
        </li>
        <?php
        $this->fireEvent('FlagContentAfter');
        ?>
    </ul>
<?php echo $this->Form->close('Flag this!');
