<?php if (!defined('APPLICATION')) exit(); ?>
<?php 
$Report = $this->Data['Plugin.Flagging.Report'];

echo Anchor($Report['UserName'], '/profile/'.$Report['UserID'].'/'.$Report['UserName']) . ' '. T('also reported this.'); ?>


<?php echo T('Reason'); ?>:
   <blockquote rel="<?php echo $Report['UserName']; ?>"><?php echo $Report['Reason']; ?></blockquote>
