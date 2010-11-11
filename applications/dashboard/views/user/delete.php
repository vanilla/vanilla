<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php printf(T('Delete User: %s'), UserAnchor($this->User)); ?></h1>
<table class="Label AltRows">
   <thead>
      <tr>
         <th><?php printf(T("Choose how to handle all of the content associated with the user account for %s (comments, messages, etc)."), Wrap($this->User->Name, 'em')); ?></th>
      </tr>
   </thead>
   <tbody>
      <tr class="Alt">
         <td>
            <h4><?php echo Anchor(T('Keep User Content'), 'user/delete/'.$this->User->UserID.'/keep'); ?></h4>
            <?php echo T("Just delete the user record, and keep all of the user's content."); ?>
         </td>
      </tr>
      <tr>
         <td>
            <h4><?php echo Anchor(T('Erase User Content'), 'user/delete/'.$this->User->UserID.'/wipe'); ?></h4>
            <?php echo T("Delete the user, but just replace all of the user's content with a message stating the user has been deleted. This will give other users a visual cue that there is missing information so they better understand how a discussion might have flowed before the deletion."); ?>
         </td>
      </tr>
      <tr class="Alt">
         <td>
            <h4><?php echo Anchor(T('Delete User Content'), 'user/delete/'.$this->User->UserID.'/delete'); ?></h4>
            <?php echo T("Delete the user and all of the user's content. This will cause discussions to be disjointed, appearing as though people are responding to content that is not there. This is a great option for removing spammer content."); ?>
         </td>
      </tr>
   </tbody>
</table>
