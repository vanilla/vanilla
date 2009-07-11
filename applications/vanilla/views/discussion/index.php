<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if ($Session->IsValid()) {
   // Bookmark link
   echo Anchor(
      '<span>*</span>',
      '/vanilla/discussion/bookmark/'.$this->Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl),
      'Bookmark' . ($this->Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
      array('title' => Gdn::Translate($this->Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark'))
   );
}
?>
<h2><?php
   if (Gdn::Config('Vanilla.Categories.Use') === TRUE) {
      echo Anchor($this->Discussion->Category, 'categories/'.$this->Discussion->CategoryID.'/'.Format::Url($this->Discussion->Category));
      echo '<span>&bull;</span>';
   }
   echo Format::Text($this->Discussion->Name);
?></h2>
<?php
   echo $this->Pager->ToString('less');
?>
<ul id="Discussion">
   <?php echo $this->FetchView('comments'); ?>
</ul>
<?php

if($this->Pager->LastPage()) {
   $this->AddDefinition('DiscussionID', $this->Data['Discussion']->DiscussionID);
   $this->AddDefinition('LastCommentID', $this->Data['Discussion']->LastCommentID);
}

echo $this->Pager->ToString('more');

// Write out the comment form
if ($this->Discussion->Closed == '1') {
   ?>
   <div class="CommentOption Closed">
      <?php echo Gdn::Translate('This discussion has been closed.'); ?>
   </div>
   <?php
} else {
   if ($Session->IsValid()) {
      echo $this->FetchView('comment', 'post');
   } else {
      ?>
      <div class="CommentOption">
         <?php echo Gdn::Translate('Want to take part in this discussion? Click one of these:'); ?>
         <?php echo Anchor('Sign In', Gdn::Authenticator()->SignInUrl($this->SelfUrl), 'Button'); ?> 
         <?php
            $Url = Gdn::Authenticator()->RegisterUrl($this->SelfUrl);
            if(!empty($Url))
               echo Anchor('Register For Membership', $Url, 'Button');
         ?>
      </div>
      <?php 
   }
}
?>
<div class="Back">
   <?php echo Anchor(Gdn::Translate('Back to Discussions'), '/'.Gdn::Config('Vanilla.Discussions.Home', 'discussions')); ?>
</div>