<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));

if ($Session->IsValid()) {
   // Bookmark link
   echo Anchor(
      '<span>*</span>',
      '/vanilla/discussion/bookmark/'.$this->Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl),
      'Bookmark' . ($this->Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
      array('title' => T($this->Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark'))
   );
}
?>
<div class="Tabs HeadingTabs DiscussionTabs">
   <ul>
      <li><?php
         if (Gdn::Config('Vanilla.Categories.Use') === TRUE) {
            echo Anchor($this->Discussion->Category, 'categories/'.$this->Discussion->CategoryUrlCode);
         } else {
            echo Anchor(T('All Discussions'), 'discussions');
         }
      ?></li>
   </ul>
   <div class="SubTab"><?php
      $DiscussionName = Gdn_Format::Text($this->Discussion->Name);
      if ($DiscussionName == '')
         $DiscussionName = T('Blank Discussion Topic');
         
      echo $DiscussionName;
   ?></div>
</div>
<?php
   $this->FireEvent('BeforeDiscussion');
   echo $this->RenderAsset('DiscussionBefore');
?>
<ul class="MessageList Discussion">
   <?php echo $this->FetchView('comments'); ?>
</ul>
<?php

if($this->Pager->LastPage()) {
   $this->AddDefinition('DiscussionID', $this->Data['Discussion']->DiscussionID);
   $LastCommentID = $this->AddDefinition('LastCommentID');
   if(is_null($LastCommentID) || $this->Data['Discussion']->LastCommentID > $LastCommentID)
      $this->AddDefinition('LastCommentID', $this->Data['Discussion']->LastCommentID);
   $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
}

echo $this->Pager->ToString('more');

// Write out the comment form
if ($this->Discussion->Closed == '1') {
   ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
      <?php echo Anchor(T('&larr; All Discussions'), 'discussions', 'TabLink'); ?>
   </div>
   <?php
} else if ($Session->IsValid() && $Session->CheckPermission('Vanilla.Comments.Add', TRUE, 'Category', $this->Discussion->CategoryID)) { 
   echo $this->FetchView('comment', 'post');
} else if ($Session->IsValid()) { ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('Commenting not allowed.'); ?></div>
      <?php echo Anchor(T('&larr; All Discussions'), 'discussions', 'TabLink'); ?>
   </div>
   <?php
} else {
   ?>
   <div class="Foot">
      <?php
      echo Anchor(T('Add a Comment'), Gdn::Authenticator()->SignInUrl($this->SelfUrl.'#Form_Body'), 'TabLink'.(SignInPopup() ? ' SignInPopup' : ''));
      ?> 
   </div>
   <?php 
}
