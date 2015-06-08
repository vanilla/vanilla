<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php printf(t('Delete User: %s'), userAnchor($this->User)); ?></h1>
<?php
echo $this->Form->errors();
if ($this->data("CanDelete")) {
    ?>
    <table class="Label AltRows">
        <thead>
        <tr>
            <th><?php printf(t('UserDeletionPrompt', "Choose how to handle all of the content associated with the user account for %s (comments, messages, etc)."), wrap(htmlspecialchars($this->User->Name), 'em')); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr class="Alt">
            <td>
                <h4><?php echo anchor(t('UserKeep', 'Keep User Content'), 'user/delete/'.$this->User->UserID.'/keep'); ?></h4>
                <?php echo t('UserKeepMessage', "Delete the user but keep the user's content."); ?>
            </td>
        </tr>
        <tr>
            <td>
                <h4><?php echo anchor(t('UserWipe', 'Blank User Content'), 'user/delete/'.$this->User->UserID.'/wipe'); ?></h4>
                <?php echo t('UserWipeMessage', "Delete the user and replace all of the user's content with a message stating the user has been deleted. This gives a visual cue that there is missing information."); ?>
            </td>
        </tr>
        <tr class="Alt">
            <td>
                <h4><?php echo anchor(t('UserDelete', 'Delete User Content'), 'user/delete/'.$this->User->UserID.'/delete'); ?></h4>
                <?php echo t('UserDeleteMessage', "Delete the user and completely remove all of the user's content. This may cause discussions to be disjointed. Best option for removing spam."); ?>
            </td>
        </tr>
        </tbody>
    </table>
<?php } ?>
