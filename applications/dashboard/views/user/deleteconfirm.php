<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php printf(t('Delete User: %s'), userAnchor($this->User)); ?></h1>
<?php
echo $this->Form->open(array('class' => 'User'));
echo $this->Form->errors();
?>
    <div class="Messages Errors" style="margin-bottom: 20px;">
        <ul>
            <li><?php printf(t("By clicking the button below, you will be deleting the user account for %s forever."), wrap(htmlspecialchars($this->User->Name), 'strong')); ?></li>
            <li><?php
                if ($this->Method == 'keep')
                    echo t("The user content will remain untouched.");
                else if ($this->Method == 'wipe')
                    echo t("All of the user content will be replaced with a message stating the user has been deleted.");
                else
                    echo t("The user content will be completely deleted.");
                ?></li>
            <li><strong><?php echo t('This action cannot be undone.'); ?></strong></li>
        </ul>
    </div>
<?php
echo $this->Form->close('Delete User Forever');
