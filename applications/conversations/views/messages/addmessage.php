<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$this->EventArguments['FormCssClass'] = 'MessageForm CommentForm';
$this->fireEvent('BeforeMessageForm');
?>
<div id="MessageForm" class="<?php echo $this->EventArguments['FormCssClass']; ?>">
    <h2 class="H"><?php echo t("Add a Message"); ?></h2>

    <div class="MessageFormWrap">
        <div class="Form-HeaderWrap">
            <div class="Form-Header">
            <span class="Author">
               <?php
               if (c('Vanilla.Comment.UserPhotoFirst', true)) {
                   echo userPhoto($Session->User);
                   echo userAnchor($Session->User, 'Username');
               } else {
                   echo userAnchor($Session->User, 'Username');
                   echo userPhoto($Session->User);
               }
               ?>
            </span>
            </div>
        </div>
        <div class="Form-BodyWrap">
            <div class="Form-Body">
                <div class="FormWrapper FormWrapper-Condensed">
                    <?php
                    echo $this->Form->open(array('id' => 'Form_ConversationMessage', 'action' => url('/messages/addmessage/')));
                    echo $this->Form->errors();
                    //               echo wrap($this->Form->textBox('Body', array('MultiLine' => true, 'class' => 'TextBox')), 'div', array('class' => 'TextBoxWrapper'));
                    echo $this->Form->bodyBox('Body', array('Table' => 'ConversationMessage', 'FileUpload' => true));
                    echo '<div class="Buttons">',
                    $this->Form->button('Send Message', array('class' => 'Button Primary')),
                    '</div>';
                    echo $this->Form->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
