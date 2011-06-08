<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));

?>
<div class="Embed">
<?php if ($this->Discussion->Closed == '1') { ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
   </div>
<?php } else { ?>
   <h3><?php echo T('Leave Comments'); ?></h3>
   <div class="MessageForm CommentForm">
      <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      echo Wrap($this->Form->TextBox('Body', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
      echo "<div class=\"Buttons\">\n";
      if ($Session->IsValid()) {
         $AuthenticationUrl = Gdn::Authenticator()->SignOutUrl(Gdn::Request()->PathAndQuery());
         echo Wrap(
            sprintf(
               T('Commenting as %1$s (%2$s)'),
               Gdn_Format::Text($Session->User->Name),
               Anchor(T('Sign Out'), $AuthenticationUrl, 'SignOut')
            ),
            'div',
            array('class' => 'Author')
         );
         echo $this->Form->Button('Post Comment', array('class' => 'Button CommentButton'));
      } else {
         $AuthenticationUrl = Gdn::Authenticator()->SignInUrl(Gdn::Request()->PathAndQuery());
         echo Anchor(T('Comment As ...'), $AuthenticationUrl, 'SignInPopup Button Stash');
      }
      echo "</div>\n";
      echo $this->Form->Close();
      ?>
   </div>
<?php } ?>
<ul class="MessageList Discussion">
   <?php
   $this->FireEvent('BeforeCommentsRender');
   $CurrentOffset = $this->Offset;
   $CommentData = $this->CommentData->Result();
   foreach ($CommentData as $Comment) {
      ++$CurrentOffset;
      $this->CurrentComment = $Comment;
      WriteComment($Comment, $this, $Session, $CurrentOffset);
   }
   ?>
</ul>
<?php
if ($this->Pager->LastPage()) {
   $LastCommentID = $this->AddDefinition('LastCommentID');
   if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
      $this->AddDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
   $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
}
echo $this->Pager->ToString('more');
?>
</div>