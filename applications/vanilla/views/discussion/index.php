<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session(); 
if (!function_exists('WriteComment'))
   include $this->FetchViewLocation('helper_functions', 'discussion');

// Wrap the discussion related content in a div.
echo '<div class="MessageList Discussion">';

// Write the page title.
echo '<!-- Page Title -->
<div id="Item_0" class="PageTitle">';

echo '<div class="Options">';

$this->FireEvent('BeforeDiscussionOptions');
WriteBookmarkLink();
WriteDiscussionOptions();
WriteAdminCheck();

echo '</div>';

echo '<h1>'.$this->Data('Discussion.Name').'</h1>';

echo "</div>\n\n";

$this->FireEvent('AfterDiscussionTitle');

// Write the initial discussion.
if ($this->Data('Page') == 1) {
   include $this->FetchViewLocation('discussion', 'discussion');
   echo '</div>'; // close discussion wrap
   
   $this->FireEvent('AfterDiscussion');
} else {
   echo '</div>'; // close discussion wrap
}

echo '<div class="CommentsWrap">';

// Write the comments.
$this->Pager->Wrapper = '<span %1$s>%2$s</span>';
echo '<span class="BeforeCommentHeading">';
$this->FireEvent('CommentHeading');
echo $this->Pager->ToString('less');
echo '</span>';

echo '<div class="DataBox DataBox-Comments">';
if ($this->Data('Comments')->NumRows() > 0)
	echo '<h2 class="CommentHeading">'.$this->Data('_CommentsHeader', T('Comments')).'</h2>';
?>
<ul class="MessageList DataList Comments">
	<?php include $this->FetchViewLocation('comments'); ?>
</ul>
<?php
$this->FireEvent('AfterComments');
if($this->Pager->LastPage()) {
   $LastCommentID = $this->AddDefinition('LastCommentID');
   if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
      $this->AddDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
   $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
}
echo '</div>';

echo '<div class="P PagerWrap">';
$this->Pager->Wrapper = '<div %1$s>%2$s</div>';
echo $this->Pager->ToString('more');
echo '</div>';
echo '</div>';

WriteCommentForm();
