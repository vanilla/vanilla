<?php if (!defined('APPLICATION')) exit();

$Flag = $this->Data['Plugin.Flagging.Data'];
$Reason = $this->Data['Plugin.Flagging.Reason'];

echo anchor($Flag['UserName'], '/profile/'.$Flag['UserID'].'/'.$Flag['UserName']).' '.t('also reported this.'); ?>


<?php echo t('Reason'); ?>:
<blockquote rel="<?php echo $Flag['UserName']; ?>"><?php echo $Reason; ?></blockquote>
