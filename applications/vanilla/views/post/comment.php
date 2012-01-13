<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$NewOrDraft = !isset($this->Comment) || property_exists($this->Comment, 'DraftID') ? TRUE : FALSE;
$Editing = isset($this->Comment);
if ($Editing) 
   $this->Form->SetFormValue('Body', $this->Comment->Body);
?>
<div class="MessageForm CommentForm">
   <h2><?php echo T($Editing ? 'Edit Comment' : 'Leave a Comment'); ?></h2>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   
   $CommentOptions = array('MultiLine' => TRUE, 'format' => GetValueR('Comment.Format', $this));
   $CommentOptions['tabindex'] = 1;
   /*
    Caused non-root users to not be able to add comments. Must take categories
    into account. Look at CheckPermission for more information.
   if (!$Session->CheckPermission('Vanilla.Comment.Add')) {
      $CommentOptions['Disabled'] = 'disabled';
      $CommentOptions['Value'] = T('You do not have permission to write new comments.');
   }
   */
   $this->FireEvent('BeforeBodyField');
   echo Wrap($this->Form->TextBox('Body', $CommentOptions), 'div', array('class' => 'TextBoxWrapper'));
   $this->FireEvent('AfterBodyField');
   echo "<div class=\"Buttons\">\n";
   $this->FireEvent('BeforeFormButtons');
   $CancelText = T('Back to Discussions');
   $CancelClass = 'Back';
   if (!$NewOrDraft) {
      $CancelText = T('Cancel');
      $CancelClass = 'MItem Cancel';
   }

   echo ' '.Gdn_Theme::Link('forumroot', $CancelText, NULL, array(
       'class' => $CancelClass
   )).' ';
   
   $ButtonOptions = array('class' => 'Button CommentButton');
   $ButtonOptions['tabindex'] = 2;
   /*
    Caused non-root users to not be able to add comments. Must take categories
    into account. Look at CheckPermission for more information.
   if (!Gdn::Session()->CheckPermission('Vanilla.Comment.Add'))
      $ButtonOptions['Disabled'] = 'disabled';
   */

   if (!$Editing && $Session->IsValid()) {
      echo Anchor(T('Preview'), '#', 'PreviewButton')."\n";
      echo Anchor(T('Edit'), '#', 'WriteButton Hidden')."\n";
      if ($NewOrDraft)
         echo Anchor(T('Save Draft'), '#', 'DraftButton')."\n";
   }
   if ($Session->IsValid())
      echo $this->Form->Button($Editing ? 'Save Comment' : 'Post Comment', $ButtonOptions);
   else {
      $AllowSigninPopup = C('Garden.SignIn.Popup');
      $Attributes = array('tabindex' => '-1');
      if (!$AllowSigninPopup)
         $Attributes['target'] = '_parent';
      
      $AuthenticationUrl = SignInUrl($this->Data('ForeignUrl', '/'));
      $CssClass = 'Button Stash';
      if ($AllowSigninPopup)
         $CssClass .= ' SignInPopup';
         
      echo Anchor(T('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
   }
   
   $this->FireEvent('AfterFormButtons');
   echo "</div>\n";
   echo $this->Form->Close();
   ?>
</div>