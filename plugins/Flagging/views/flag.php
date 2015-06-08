<?php if (!defined('APPLICATION')) exit(); ?>
<?php
$UcContext = ucfirst($this->Data['Plugin.Flagging.Data']['Context']);
$ElementID = $this->Data['Plugin.Flagging.Data']['ElementID'];
$URL = $this->Data['Plugin.Flagging.Data']['URL'];
$Title = sprintf("Flag this %s", ucfirst($this->Data['Plugin.Flagging.Data']['Context']));
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
            <?php echo t('FlagLinkContent', 'Link to content:').' '.anchor(t('FlagLinkFormat', "{$UcContext} #{$ElementID}"), $URL); ?> &ndash;
            <?php echo $this->Data['Plugin.Flagging.Data']['ElementAuthor']; ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Reason', 'Plugin.Flagging.Reason');
            echo $this->Form->textBox('Plugin.Flagging.Reason', array('MultiLine' => TRUE));
            ?>
        </li>
        <?php
        $this->fireEvent('FlagContentAfter');
        ?>
    </ul>
<?php echo $this->Form->close('Flag this!');
