<?php if (!defined('APPLICATION')) exit(); ?>
<?php 
$Flag = $this->Data['Plugin.Flagging.Data']; // shortcut to original post meta data
$UserName = GetValue('UserName', $this->Report);
$UserID = GetValue('UserID', $this->Report);
$FlaggedContent = GetValue('FlaggedContent', $this->Report);

echo Anchor($UserName, '/profile/'.$UserID.'/'.$UserName) . ' '. T('reported'); 

if ($Flag['Context'] == 'comment') 
   echo ' ' . T('a comment in');
?> <strong><?php echo Anchor(GetValue('DiscussionName', $this->Report), $Flag['URL']); ?></strong>
   
<?php echo T('Reason'); ?>:
   <blockquote rel="<?php echo $UserName; ?>"><?php echo GetValue('Reason', $this->Report); ?></blockquote>
<?php echo T('Flagged Content'); ?>:
   <blockquote rel="<?php echo $Flag['ElementAuthor']; ?>"><?php 
   
   echo substr($FlaggedContent, 0 , 500); 
   if(strlen($FlaggedContent) > 500)
      echo '&hellip;';
   
   ?></blockquote>
<?php echo Anchor(T('ViewFlagged', 'View &raquo;'), $Flag['URL']); ?>