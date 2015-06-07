<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<div class="MessageForm EditCommentForm">
    <div class="Form-BodyWrap">
        <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
                <?php
                echo $this->Form->open();
                echo $this->Form->errors();
                echo $this->Form->bodyBox('Body', array('Table' => 'Comment', 'tabindex' => 1, 'FileUpload' => true));
                echo "<div class=\"Buttons\">\n";
                echo anchor(t('Cancel'), '/', 'Button Cancel').' ';
                echo $this->Form->button('Save Comment', array('class' => 'Button Primary CommentButton', 'tabindex' => 2));
                echo "</div>\n";
                echo $this->Form->close();
                ?>
            </div>
        </div>
    </div>
</div>
