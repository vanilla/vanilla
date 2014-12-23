<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Whispers-Form">
   <?php
   if (Gdn::Controller()->Data('Discussion.Attributes.WhisperConversationID')):
      // The form is in private conversation mode.
      echo '<div class="Info">';

      if (Gdn::Session()->CheckPermission('Conversations.Moderation.Manage')) {
         $Query = array(
             'tk' => Gdn::Session()->TransientKey(),
             'discussionid' => Gdn::Controller()->Data('Discussion.DiscussionID'));
         echo '<span style="float: right">'.Anchor(T('Continue in Public...'), '/discussion/makepublic?'.http_build_query($Query), '', array('title' => T('Continue this discussion in public.'))).'</span>';
      }
   
      echo T('New comments will be in private between:');
   
      echo '<div class="P">';
      foreach (Gdn::Controller()->Data('WhisperUsers') as $User) {
         echo '<span>',
            UserPhoto($User, array('ImageClass' => 'ProfilePhotoSmall')),
            ' '.UserAnchor($User),
            '</span> ';
      }
      echo '</div>';
   
      echo '</div>';
      
   else:
      // Here is the general whisper form.
      $Conversations = $this->Conversations;
      $HasPermission = Gdn::Session()->CheckPermission('Plugins.Whispers.Allow');

      echo '<div class="P">';

      if ($HasPermission)
         echo $this->Form->CheckBox('Whisper', T('Whisper'));

      echo '</div>';

      if ($HasPermission) {
         echo '<div id="WhisperForm">';

         echo '<div class="Info">',
            T('Whispering will start a private conversation.', 'Whispering will start a private conversation associated with this discussion.'),
            '</div>';

         if (count($Conversations) > 0) {
            echo '<ul>';

            foreach ($Conversations as $Conversation) {
               $Participants = GetValue('Participants', $Conversation);
               $ConversationName = '';
               foreach ($Participants as $User) {
                  $ConversationName = ConcatSep(', ', $ConversationName, htmlspecialchars(GetValue('Name', $User)));
               }

               echo '<li>'.$this->Form->Radio('ConversationID', $ConversationName, array('Value' => GetValue('ConversationID', $Conversation))).'</li>';
            }
            echo '<li>'.$this->Form->Radio('ConversationID', T('New Whisper'), array('Value' => '')).'</li>';

            echo '</ul>';
         }

         echo Wrap($this->Form->TextBox('To', array('MultiLine' => TRUE, 'class' => 'MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));

         echo '</div>';
      }
   
   endif;
   ?>
</div>