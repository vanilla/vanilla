<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php printf(t('Delete User: %s'), userAnchor($this->User)); ?></h1>
<?php
if ($this->data("CanDelete")) {
    ?>
<div class="padded">
    <?php printf(t('UserDeletionPrompt', "Choose how to handle all of the content associated with the user account for %s (comments, messages, etc)."), wrap(htmlspecialchars($this->User->Name), 'em')); ?></th>
</div>
<div class="form-group">
    <div class="label-wrap-wide"><?php echo t('UserKeepMessage', "Delete the user but keep the user's content."); ?></div>
    <div class="input-wrap-right"><?php echo anchor(t('UserKeep', 'Keep User Content'), 'user/delete/'.$this->User->UserID.'/keep', 'btn btn-secondary js-modal', ['data-css-class' => 'modal-sm']); ?></div>
</div>
<div class="form-group">
    <div class="label-wrap-wide"><?php echo t('UserWipeMessage', "Delete the user and replace all of the user's content with a message stating the user has been deleted. This gives a visual cue that there is missing information."); ?></div>
    <div class="input-wrap-right"><?php echo anchor(t('UserWipe', 'Blank User Content'), 'user/delete/'.$this->User->UserID.'/wipe', 'btn btn-secondary js-modal', ['data-css-class' => 'modal-sm']); ?></div>
</div>
<div class="form-group">
    <div class="label-wrap-wide"><?php echo t('UserDeleteMessage', "Delete the user and completely remove all of the user's content. This may cause discussions to be disjointed. Best option for removing spam."); ?></div>
    <div class="input-wrap-right"><?php echo anchor(t('UserDelete', 'Delete User Content'), 'user/delete/'.$this->User->UserID.'/delete', 'btn btn-secondary js-modal', ['data-css-class' => 'modal-sm']); ?></div>
</div>

<?php } ?>
