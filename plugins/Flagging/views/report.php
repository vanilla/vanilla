<?php if (!defined('APPLICATION')) exit(); ?>
<?php
$Flag = $this->Data['Plugin.Flagging.Data'];
$Report = $this->Data['Plugin.Flagging.Report'];
$Reason = $this->Data['Plugin.Flagging.Reason'];

printf(t('%s reported%s <strong>%s</strong>'), anchor($Flag['UserName'], '/profile/'.$Flag['UserID'].'/'.$Flag['UserName']), ($Flag['Context'] == 'comment') ? t(' a comment in') : null, anchor($Report['DiscussionName'], $Flag['URL']));

?>

<?php echo t('Reason'); ?>:
    <blockquote rel="<?php echo $Flag['UserName']; ?>"><?php echo $Reason; ?></blockquote>
<?php echo t('Flagged Content'); ?>:
    <blockquote rel="<?php echo $Flag['ElementAuthor']; ?>"><?php

        echo substr($Report['FlaggedContent'], 0, 500);
        if (strlen($Report['FlaggedContent']) > 500)
            echo '&#8230;';

        ?></blockquote>
<?php echo anchor(t('ViewFlagged', 'View &raquo;'), $Flag['URL']); ?>
