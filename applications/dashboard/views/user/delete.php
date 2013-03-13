<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php printf(T('Delete User: %s'), UserAnchor($this->User)); ?></h1>
<?php 
   echo $this->Form->Errors(); 
   if ($this->Data("CanDelete")) {
?>
<table class="Label AltRows">
   <thead>
      <tr>
         <th><?php printf(T('UserDeletionPrompt', "Choose how to handle all of the content associated with the user account for %s (comments, messages, etc)."), Wrap(htmlspecialchars($this->User->Name), 'em')); ?></th>
      </tr>
   </thead>
   <tbody>
      <tr class="Alt">
         <td>
            <h4><?php echo Anchor(T('UserKeep', 'Keep User Content'), 'user/delete/'.$this->User->UserID.'/keep'); ?></h4>
            <?php echo T('UserKeepMessage', "Delete the user but keep the user's content."); ?>
         </td>
      </tr>
      <tr>
         <td>
            <h4><?php echo Anchor(T('UserWipe', 'Blank User Content'), 'user/delete/'.$this->User->UserID.'/wipe'); ?></h4>
            <?php echo T('UserWipeMessage', "Delete the user and replace all of the user's content with a message stating the user has been deleted. This gives a visual cue that there is missing information."); ?>
         </td>
      </tr>
      <tr class="Alt">
         <td>
            <h4><?php echo Anchor(T('UserDelete', 'Remove User Content'), 'user/delete/'.$this->User->UserID.'/delete'); ?></h4>
            <?php echo T('UserDeleteMessage', "Delete the user and completely remove all of the user's content. This may cause discussions to be disjointed. Best option for removing spam."); ?>
         </td>
      </tr>
   </tbody>
</table>
<?php } ?>