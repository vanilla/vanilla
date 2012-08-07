<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$this->EventArguments['FormCssClass'] = 'MessageForm CommentForm';
$this->FireEvent('BeforeMessageForm');
?>
<div id="MessageForm" class="<?php echo $this->EventArguments['FormCssClass']; ?>">
   <h2 class="H"><?php echo T("Add a Message"); ?></h2>
   <div class="MessageFormWrap">
      <div class="Form-HeaderWrap">
         <div class="Form-Header">
            <span class="Author">
               <?php
               if (C('Vanilla.Comment.UserPhotoFirst', TRUE)) {
                  echo UserPhoto($Session->User);
                  echo UserAnchor($Session->User, 'Username');
               } else {
                  echo UserAnchor($Session->User, 'Username');
                  echo UserPhoto($Session->User);
               }
               ?>
            </span>
         </div>
      </div>
      <div class="Form-BodyWrap">
         <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
               <?php
               echo $this->Form->Open(array('id' => 'Form_ConversationMessage', 'action' => Url('/messages/addmessage/')));
               echo $this->Form->Errors();
//               echo Wrap($this->Form->TextBox('Body', array('MultiLine' => TRUE, 'class' => 'TextBox')), 'div', array('class' => 'TextBoxWrapper'));
               echo $this->Form->BodyBox('Body', array('Table' => 'ConversationMessage'));
               echo '<div class="Buttons">',
                  $this->Form->Button('Send Message', array('class' => 'Button Primary')),
                  '</div>';
               echo $this->Form->Close();
               ?>
            </div>
         </div>
      </div>
   </div>
</div>