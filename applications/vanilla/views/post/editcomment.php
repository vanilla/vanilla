<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$this->fireEvent('BeforeCommentForm');
?>
<div class="MessageForm EditCommentForm">
    <div class="Form-BodyWrap">
        <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
                <?php
                echo $this->Form->open();
                echo $this->Form->errors();
                $this->fireEvent('BeforeBodyField');
                echo $this->Form->bodyBox('Body', ['Table' => 'Comment', 'FileUpload' => true, 'placeholder' => t('Type your comment'), 'title' => t('Type your comment')]);
                $this->fireEvent('AfterBodyField');
                echo "<div class=\"Buttons\">\n";
                $this->fireEvent('BeforeFormButtons');
                echo anchor(t('Cancel'), '/', 'Button Cancel').' ';
                echo $this->Form->button('Save Comment', ['class' => 'Button Primary CommentButton']);
                $this->fireEvent('AfterFormButtons');
                echo "</div>\n";
                echo $this->Form->close();
                ?>
            </div>
        </div>
    </div>
</div>
