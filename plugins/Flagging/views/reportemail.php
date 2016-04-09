<?php if (!defined('APPLICATION')) exit();

$discussionID = $this->data('Plugin.Flagging.DiscussionID');
$flag         = $this->data('Plugin.Flagging.Data');
$report       = $this->data('Plugin.Flagging.Report');
$reason       = $this->data('Plugin.Flagging.Reason');

echo t('Discussion'); ?>: <?php echo val('DiscussionName', $report); ?>


<?php echo externalUrl($flag['URL']); ?>


<?php echo t('Reason').": {$reason}"; ?>


<?php echo t('FlaggedBy', 'Reported by:')." {$flag['UserName']}"; ?>


<?php if ($discussionID) {
    echo t('FlagDiscuss', 'Discuss it').': '.externalUrl('discussion/'.$DiscussionID);
} ?>
