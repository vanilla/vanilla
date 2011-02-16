<?php if (!defined('APPLICATION')) exit(); ?>
<?php 
$Flag = $this->Data['Plugin.Flagging.Data'];
$Report = $this->Data['Plugin.Flagging.Report'];
$DiscussionID = $this->Data['Plugin.Flagging.DiscussionID'];

echo T('Discussion'); ?>: <?php echo $Report['DiscussionName']; ?>

<?php echo ExternalUrl($Flag['URL']); ?>


<?php echo T('Reason') . ': ' . $Report['Reason']; ?>


<?php echo T('Flagged by') . ': ' . $Report['UserName']; ?>

<?php echo T('Discuss it') . ': ' . ExternalUrl('discussion/'.$DiscussionID); ?>