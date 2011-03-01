<?php if (!defined('APPLICATION')) exit();

$Flag = $this->Data['Plugin.Flagging.Data'];
$Reason = $this->Data['Plugin.Flagging.Reason'];

echo Anchor($Flag['UserName'], '/profile/'.$Flag['UserID'].'/'.$Flag['UserName']) . ' '. T('also reported this.'); ?>


<?php echo T('Reason'); ?>:
   <blockquote rel="<?php echo $Flag['UserName']; ?>"><?php echo $Reason; ?></blockquote>
