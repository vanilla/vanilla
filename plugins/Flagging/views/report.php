<?php if (!defined('APPLICATION')) exit(); ?>
<?php 
$Flag = $this->Data['Plugin.Flagging.Data'];
$Report = $this->Data['Plugin.Flagging.Report'];

echo Anchor($Flag['UserName'], '/profile/'.$Flag['UserID'].'/'.$Flag['UserName']) . ' '. T('reported'); 

if ($Flag['Context'] == 'comment') 
   echo ' ' . T('a comment in');
?> <strong><?php echo Anchor($Report['DiscussionName'], $Flag['URL']); ?></strong>
   
<?php echo T('Reason'); ?>:
   <blockquote rel="<?php echo $Flag['UserName']; ?>"><?php echo $Report['Reason']; ?></blockquote>
<?php echo T('Flagged Content'); ?>:
   <blockquote rel="<?php echo $Flag['ElementAuthor']; ?>"><?php 
   
   echo substr($Report['FlaggedContent'], 0 , 500); 
   if(strlen($Report['FlaggedContent']) > 500)
      echo '&hellip;';
   
   ?></blockquote>
<?php echo Anchor(T('ViewFlagged', 'View &raquo;'), $Flag['URL']); ?>