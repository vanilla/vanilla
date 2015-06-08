<?php if (!defined('APPLICATION')) exit(); ?>
<?php
$Flag = $this->Data['Plugin.Flagging.Data'];
$Report = $this->Data['Plugin.Flagging.Report'];
$DiscussionID = $this->Data['Plugin.Flagging.DiscussionID'];
$Reason = $this->Data['Plugin.Flagging.Reason'];

echo t('Discussion'); ?>: <?php if (isset($Report['DiscussionName'])) echo $Report['DiscussionName']; ?>

<?php echo ExternalUrl($Flag['URL']); ?>


<?php echo t('Reason').': '.$Reason; ?>


<?php echo t('FlaggedBy', 'Reported by:').' '.$Flag['UserName']; ?>

<?php if ($DiscussionID) echo t('FlagDiscuss', 'Discuss it').': '.ExternalUrl('discussion/'.$DiscussionID); ?>
