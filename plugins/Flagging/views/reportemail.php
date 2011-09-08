<?php if (!defined('APPLICATION')) exit(); ?>
<?php 
$Flag = $this->Data['Plugin.Flagging.Data'];
$Report = $this->Data['Plugin.Flagging.Report'];
$DiscussionID = $this->Data['Plugin.Flagging.DiscussionID'];
$Reason = $this->Data['Plugin.Flagging.Reason'];

echo T('Discussion'); ?>: <?php if (isset($Report['DiscussionName'])) echo $Report['DiscussionName']; ?>

<?php echo ExternalUrl($Flag['URL']); ?>


<?php echo T('Reason') . ': ' . $Reason; ?>


<?php echo T('FlaggedBy', 'Reported by:') .' '. $Flag['UserName']; ?>

<?php if ($DiscussionID) echo T('FlagDiscuss', 'Discuss it') . ': ' . ExternalUrl('discussion/'.$DiscussionID); ?>