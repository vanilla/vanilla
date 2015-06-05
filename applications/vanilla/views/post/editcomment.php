<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="MessageForm EditCommentForm">
    <div class="Form-BodyWrap">
        <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
                <?php
                echo $this->Form->Open();
                echo $this->Form->Errors();
                echo $this->Form->BodyBox('Body', array('Table' => 'Comment', 'tabindex' => 1, 'FileUpload' => true));
                echo "<div class=\"Buttons\">\n";
                echo Anchor(T('Cancel'), '/', 'Button Cancel').' ';
                echo $this->Form->Button('Save Comment', array('class' => 'Button Primary CommentButton', 'tabindex' => 2));
                echo "</div>\n";
                echo $this->Form->Close();
                ?>
            </div>
        </div>
    </div>
</div>
