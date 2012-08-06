<?php if (!defined('APPLICATION')) exit(); ?>
<?php 
$Flag = $this->Data['Plugin.Flagging.Data'];
$Report = $this->Data['Plugin.Flagging.Report'];
$Reason = $this->Data['Plugin.Flagging.Reason'];

printf(T('%s reported%s <strong>%s</strong>'), Anchor($Flag['UserName'], '/profile/'.$Flag['UserID'].'/'.$Flag['UserName']), ($Flag['Context'] == 'comment') ? T(' a comment in') : null, Anchor($Report['DiscussionName'], $Flag['URL'])); 

?>
   
<?php echo T('Reason'); ?>:
   <blockquote rel="<?php echo $Flag['UserName']; ?>"><?php echo $Reason; ?></blockquote>
<?php echo T('Flagged Content'); ?>:
   <blockquote rel="<?php echo $Flag['ElementAuthor']; ?>"><?php 
   
   echo substr($Report['FlaggedContent'], 0 , 500); 
   if(strlen($Report['FlaggedContent']) > 500)
      echo '&#8230;';
   
   ?></blockquote>
<?php echo Anchor(T('ViewFlagged', 'View &raquo;'), $Flag['URL']); ?>