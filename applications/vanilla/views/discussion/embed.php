<?php if (!defined('APPLICATION')) exit();
$Discussion = $this->Data('Discussion');
$ForeignSource = $this->Data('ForeignSource');
$SortComments = $this->Data('SortComments');
$HasCommentData = property_exists($this, 'CommentData');
$Session = Gdn::Session();
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));

?>
<div class="Embed">
<?php
echo '<span class="BeforeCommentHeading">';
$this->FireEvent('CommentHeading');
echo '</span>';
         $AuthenticationUrl = SignOutUrl($ReturnUrl);

if ($SortComments == 'desc')
   WriteEmbedCommentForm();
else if ($HasCommentData && $this->CommentData->NumRows() > 0)
   echo Wrap(T('Comments'), 'h2');
?>
<ul class="DataList MessageList Comments">
   <?php
   if ($HasCommentData) {
      $this->FireEvent('BeforeCommentsRender');
      $CurrentOffset = $this->Offset;
      $CommentData = $this->CommentData->Result();
      foreach ($CommentData as $Comment) {
         ++$CurrentOffset;
         $this->CurrentComment = $Comment;
         WriteComment($Comment, $this, $Session, $CurrentOffset);
      }
   }
   ?>
</ul>
<?php
if ($HasCommentData) {
   if ($this->Pager->LastPage()) {
      $LastCommentID = $this->AddDefinition('LastCommentID');
      if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
         $this->AddDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
      $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
   }
   
   // Send the user to the discussion in the forum when paging
   if (C('Garden.Embed.PageToForum') && $this->Pager->HasMorePages()) {
      $DiscussionUrl = DiscussionUrl($Discussion).'#latest';
      echo '<div class="PageToForum Foot">';
      echo Anchor(T('More Comments'), $DiscussionUrl);
      echo '</div>';
   } else 
      echo $this->Pager->ToString('more');
}

if ($SortComments != 'desc')
   WriteEmbedCommentForm();

?>
</div>