<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$DiscussionName = Gdn_Format::Text($this->Discussion->Name);
if ($DiscussionName == '')
   $DiscussionName = T('Blank Discussion Topic');

$this->EventArguments['DiscussionName'] = &$DiscussionName;
$this->FireEvent('BeforeDiscussionTitle');

if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));

$PageClass = '';
if($this->Pager->FirstPage()) 
	$PageClass = 'FirstPage'; 
	
?>
<div class="Tabs HeadingTabs DiscussionTabs <?php echo $PageClass; ?>">
   <?php
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

   <ul>
      <li><?php
         if (C('Vanilla.Categories.Use') == TRUE) {
            echo Anchor($this->Discussion->Category, 'categories/'.$this->Discussion->CategoryUrlCode, 'TabLink');
         } else {
            echo Anchor(T('All Discussions'), 'discussions', 'TabLink');
         }
      ?></li>
   </ul>
   <div class="SubTab"><?php echo $DiscussionName; ?></div>
</div>
<?php $this->FireEvent('BeforeDiscussion'); ?>
<ul class="DataList MessageList Discussion <?php echo $PageClass; ?>">
   <?php echo $this->FetchView('comments'); ?>
</ul>
<?php
$this->FireEvent('AfterDiscussion');
if($this->Pager->LastPage()) {
   $LastCommentID = $this->AddDefinition('LastCommentID');
   if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
      $this->AddDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
   $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
}

echo $this->Pager->ToString('more');

// Write out the comment form
if ($this->Discussion->Closed == '1') {
   ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
      <?php echo Anchor(T('All Discussions'), 'discussions', 'TabLink'); ?>
   </div>
   <?php
} else if ($Session->IsValid() && $Session->CheckPermission('Vanilla.Comments.Add', TRUE, 'Category', $this->Discussion->PermissionCategoryID)) {
   echo $this->FetchView('comment', 'post');
} else if ($Session->IsValid()) { ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('Commenting not allowed.'); ?></div>
      <?php echo Anchor(T('All Discussions'), 'discussions', 'TabLink'); ?>
   </div>
   <?php
} else {
   ?>
   <div class="Foot">
      <?php
      echo Anchor(T('Add a Comment'), SignInUrl($this->SelfUrl.(strpos($this->SelfUrl, '?') ? '&' : '?').'post#Form_Body'), 'TabLink'.(SignInPopup() ? ' SignInPopup' : ''));
      ?> 
   </div>
   <?php 
}
